<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class FarmAlert extends Command
{
    /**
     * Signature untuk menjalankan bot: php artisan farm:run-bot
     */
    protected $signature = 'farm:run-bot';
    protected $description = 'Bot Broilink: Deep Link Auth & Smart Monitoring';

    protected $telegramToken;

    public function __construct()
    {
        parent::__construct();
        // Mengambil token dari .env
        $this->telegramToken = env('TELEGRAM_BOT_TOKEN');
    }

    /**
     * LOOP UTAMA (Jantung Bot)
     */
    public function handle()
    {
        $this->info("========================================");
        $this->info("🤖 BOT BROILINK FULL SYSTEM STARTING...");
        $this->info("   - Token: " . substr($this->telegramToken, 0, 10) . "...");
        $this->info("   - Mode: Deep Link & Monitoring Aktif");
        $this->info("========================================");

        while (true) {
            // 1. Cek Pesan Masuk (Untuk Auth / Link Akun)
            $this->handleIncomingMessages();

            // 2. Cek Kondisi Kandang (Untuk Peringatan Bahaya)
            $this->checkFarmConditions();

            // Istirahat 2 detik biar server tidak berat
            sleep(2);
        }
    }

    // =========================================================================
    // BAGIAN 1: MENANGANI PESAN & TOKEN (/start TOKEN)
    // =========================================================================
    private function handleIncomingMessages()
    {
        $lastUpdateId = Cache::get('tele_last_id', 0);

        try {
            $response = Http::timeout(5)->get("https://api.telegram.org/bot{$this->telegramToken}/getUpdates", [
                'offset' => $lastUpdateId + 1,
                'timeout' => 1
            ]);

            if ($response->successful()) {
                $updates = $response->json()['result'] ?? [];

                foreach ($updates as $update) {
                    Cache::put('tele_last_id', $update['update_id']);

                    if (isset($update['message']['text'])) {
                        $chatId = $update['message']['chat']['id'];
                        $text = trim($update['message']['text']);
                        $firstName = $update['message']['from']['first_name'] ?? 'Juragan';

                        // --- LOGIKA START DENGAN TOKEN (Deep Linking) ---
                        if (str_starts_with($text, '/start')) {
                            $parts = explode(' ', $text);

                            // Jika ada token (contoh: /start a8s7d8a7sd...)
                            if (count($parts) > 1) {
                                $token = $parts[1];
                                $this->verifikasiViaToken($chatId, $token);
                            } else {
                                // Start biasa (Tanpa token)
                                $msg = "Halo $firstName! 👋\n\nSelamat datang di Bot Broilink.\n\nUntuk menghubungkan akun, silakan:\n1. Login ke Website Broilink\n2. Klik tombol **'Aktifkan Notifikasi'** di Dashboard/Profil\n3. Anda akan diarahkan otomatis ke sini.";
                                $this->sendMessage($chatId, $msg);
                            }
                        }
                        // --- FITUR CEK CONFIG ---
                        elseif ($text == '/cekiot') {
                            $this->cekSettinganIot($chatId);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error("Error Telegram Connection: " . $e->getMessage());
        }
    }

    /**
     * Verifikasi Token yang dikirim dari Web
     */
    private function verifikasiViaToken($chatId, $token)
    {
        // Cek Cache (Apakah tiket ini valid?)
        $userId = Cache::get('tele_connect_' . $token);

        if (!$userId) {
            $this->sendMessage($chatId, "❌ Link kadaluarsa atau tidak valid.\nSilakan kembali ke website dan klik tombol notifikasi lagi.");
            return;
        }

        $user = DB::table('users')->where('user_id', $userId)->first();
        if (!$user) return;

        // Reset koneksi lama (Hapus data lama jika ada)
        DB::table('user_telegram')->where('telegram_chat_id', $chatId)->delete();
        DB::table('user_telegram')->where('user_id', $userId)->delete();

        // Simpan User Baru
        DB::table('user_telegram')->insert([
            'user_id' => $userId,
            'telegram_chat_id' => $chatId,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Hapus tiket agar tidak bisa dipakai ulang (Security)
        Cache::forget('tele_connect_' . $token);

        $this->sendMessage($chatId, "✅ **BERHASIL TERHUBUNG!**\n\nHalo {$user->name}, akun Telegram ini telah sukses terhubung ke sistem Broilink\n\nKetik `/cekiot` untuk melihat konfigurasi kandang.");
    }

    /**
     * Fitur Cek Config Kandang
     */
    private function cekSettinganIot($chatId)
    {
        $teleUser = DB::table('user_telegram')->where('telegram_chat_id', $chatId)->first();
        if (!$teleUser) { $this->sendMessage($chatId, "❌ Belum terhubung. Silakan hubungkan akun via website."); return; }

        $farms = DB::table('farms')
                ->where('owner_id', $teleUser->user_id)
                ->orWhere('peternak_id', $teleUser->user_id)
                ->get();

        if ($farms->isEmpty()) { $this->sendMessage($chatId, "⚠️ Anda tidak memiliki kandang aktif."); return; }

        foreach ($farms as $farm) {
            $config = DB::table('farm_config')->where('farm_id', $farm->farm_id)->pluck('value', 'parameter_name')->toArray();

            $msg = "🏠 **SETTING: {$farm->farm_name}**\n";
            $msg .= "----------------------------------\n";
            $msg .= "🌡 **SUHU**\n";
            $msg .= "• Normal: " . ($config['suhu_normal_min']??0) . " - " . ($config['suhu_normal_max']??0) . "\n";
            $msg .= "• Bahaya: <" . ($config['suhu_kritis_rendah']??0) . " atau >" . ($config['suhu_kritis_tinggi']??0) . "\n\n";
            $msg .= "☣️ **AMONIA**\n";
            $msg .= "• Bahaya: >" . ($config['amonia_kritis']??0) . "\n";
            $msg .= "----------------------------------";

            $this->sendMessage($chatId, $msg);
        }
    }

    // =========================================================================
    // BAGIAN 2: MONITORING KANDANG (Logic Lengkap)
    // =========================================================================
    private function checkFarmConditions()
    {
        $farms = DB::table('farms')->get();

        foreach ($farms as $farm) {
            // 1. Ambil Data IoT Terakhir
            $data = DB::table('iot_data')->where('farm_id', $farm->farm_id)->orderBy('timestamp', 'desc')->first();

            // Kalau data kosong, skip
            if (!$data) continue;

            // 2. Cek Cache Anti-Spam (Jangan kirim notif berulang untuk data ID yg sama)
            $cacheKey = "alert_iot_" . $data->id;
            if (Cache::has($cacheKey)) continue;

            // 3. Ambil Config Kandang (Default 0 jika belum disetting)
            $rawConfig = DB::table('farm_config')->where('farm_id', $farm->farm_id)->pluck('value', 'parameter_name')->toArray();

            $cfg = [
                'suhu_n_min' => $rawConfig['suhu_normal_min'] ?? 0,
                'suhu_n_max' => $rawConfig['suhu_normal_max'] ?? 0,
                'suhu_k_min' => $rawConfig['suhu_kritis_rendah'] ?? 0,
                'suhu_k_max' => $rawConfig['suhu_kritis_tinggi'] ?? 0,

                'humi_n_min' => $rawConfig['kelembapan_normal_min'] ?? 0,
                'humi_n_max' => $rawConfig['kelembapan_normal_max'] ?? 0,
                'humi_k_min' => $rawConfig['kelembapan_kritis_rendah'] ?? 0,
                'humi_k_max' => $rawConfig['kelembapan_kritis_tinggi'] ?? 0,

                'amo_max'    => $rawConfig['amonia_max'] ?? 0,
                'amo_kritis' => $rawConfig['amonia_kritis'] ?? 0,
            ];

            $messages = [];
            $farmStatus = 'AMAN';
            $s_status = 'AMAN';
            $h_status = 'AMAN';
            $a_status = 'AMAN';

            // --- A. LOGIKA SUHU ---
            $suhu = $data->temperature;
            // Cek Bahaya (Hanya jika config diisi > 0)
            if ($cfg['suhu_k_max'] > 0 && ($suhu <= $cfg['suhu_k_min'] || $suhu >= $cfg['suhu_k_max'])) {
                $s_status = 'BAHAYA';
                $messages[] = "🔥 Suhu: {$suhu}°C (Kritis)";
            }
            // Cek Waspada
            elseif ($cfg['suhu_n_max'] > 0 && ($suhu < $cfg['suhu_n_min'] || $suhu > $cfg['suhu_n_max'])) {
                $s_status = 'WASPADA';
                $messages[] = "⚠️ Suhu: {$suhu}°C (Waspada)";
            }

            // --- B. LOGIKA KELEMBAPAN ---
            $humi = $data->humidity;
            if ($cfg['humi_k_max'] > 0 && ($humi <= $cfg['humi_k_min'] || $humi >= $cfg['humi_k_max'])) {
                $h_status = 'BAHAYA';
                $messages[] = "💧 Kelembapan: {$humi}% (Kritis)";
            }
            elseif ($cfg['humi_n_max'] > 0 && ($humi < $cfg['humi_n_min'] || $humi > $cfg['humi_n_max'])) {
                $h_status = 'WASPADA';
                $messages[] = "⚠️ Kelembapan: {$humi}% (Waspada)";
            }

            // --- C. LOGIKA AMONIA ---
            $amo = $data->ammonia;
            if ($cfg['amo_kritis'] > 0 && $amo >= $cfg['amo_kritis']) {
                $a_status = 'BAHAYA';
                $messages[] = "☣️ Amonia: {$amo} ppm (Kritis)";
            }
            elseif ($cfg['amo_max'] > 0 && $amo > $cfg['amo_max']) {
                $a_status = 'WASPADA';
                $messages[] = "⚠️ Amonia: {$amo} ppm (Waspada)";
            }

            // --- D. HITUNG STATUS AGREGAT ---
            // Prioritas: Jika ada SATU saja BAHAYA -> Kandang BAHAYA
            if ($s_status == 'BAHAYA' || $h_status == 'BAHAYA' || $a_status == 'BAHAYA') {
                $farmStatus = 'BAHAYA';
            }
            // Jika tidak ada bahaya, baru cek Waspada
            elseif ($s_status == 'WASPADA' || $h_status == 'WASPADA' || $a_status == 'WASPADA') {
                $farmStatus = 'WASPADA';
            }

            // --- E. KIRIM NOTIFIKASI (JIKA TIDAK AMAN) ---
            if ($farmStatus != 'AMAN') {
                $icon = ($farmStatus == 'BAHAYA') ? '🚨' : '⚠️';
                $msg = "{$icon} **STATUS: {$farmStatus}**\n";
                $msg .= "📍 {$farm->farm_name}\n";
                $msg .= "⏰ " . date('H:i', strtotime($data->timestamp)) . "\n\n";
                $msg .= implode("\n", $messages);
                $msg .= "\n\n_Segera cek kondisi kandang!_";

                // Cari Owner & Peternak Kandang Ini
                $users = DB::table('user_telegram')
                        ->whereIn('user_id', [$farm->owner_id, $farm->peternak_id])
                        ->get();

                foreach ($users as $u) {
                    $this->sendMessage($u->telegram_chat_id, $msg);
                }

                // Tandai data ini sudah terkirim notifnya (Expire 30 menit)
                Cache::put($cacheKey, true, 1800);
            }
        }
    }

    private function sendMessage($chatId, $text) {
        if (!$this->telegramToken) return;
        try {
            Http::post("https://api.telegram.org/bot{$this->telegramToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown'
            ]);
        } catch (\Exception $e) {
            // Silent fail
        }
    }
}
