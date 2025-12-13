<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class FarmAlert extends Command
{
    protected $signature = 'farm:run-bot';
    protected $description = 'Bot Broilink (Command Version)';

    // --- GANTI TOKEN DARI BOTFATHER DISINI ---
    protected $telegramToken = '8342385562:AAE19ZYTzRBgxDnmthRt-YHmUozWw_femYg';

    public function handle()
    {
        // Pesan saat bot pertama kali jalan di terminal
        $this->info("========================================");
        $this->info("🤖 BOT BROILINK BERJALAN!");
        $this->info("========================================");
        $this->info("Command List:");
        $this->info("1. /start");
        $this->info("2. /profile");
        $this->info("3. /cemail <email>");
        $this->info("4. /verifikasi <email>");
        $this->info("========================================");

        while (true) {
            $this->handleIncomingMessages();
            $this->checkFarmConditions();

            // Jeda 2 detik (Biar CPU gak panas)
            sleep(2);
        }
    }

    // --- 1. HANDLE CHAT (VERSI COMMAND) ---
    private function handleIncomingMessages()
    {
        $lastUpdateId = Cache::get('tele_last_id', 0);

        try {
            $response = Http::get("https://api.telegram.org/bot{$this->telegramToken}/getUpdates", [
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
                        $firstName = $update['message']['from']['first_name'] ?? 'User';

                        // --- LOGIC BARU ---
                        if ($text == '/start') {
                            $msg = "Halo $firstName! 👋\n\n**Daftar Perintah:**\n";
                            $msg .= "👤 `/profile` - Cek data akun\n";
                            $msg .= "🔄 `/cemail email_baru` - Ganti/Verifikasi Email\n\n";
                            $msg .= "Contoh: `/cemail ahmad@gmail.com`";
                            $this->sendMessage($chatId, $msg);
                        }
                        elseif ($text == '/profile') {
                            $this->cekProfil($chatId);
                        }
                        // Handle /cemail DAN /verifikasi (Fungsinya sama)
                        elseif (str_starts_with($text, '/cemail') || str_starts_with($text, '/verifikasi')) {
                            // Ambil email setelah spasi
                            $parts = explode(' ', $text);
                            $email = $parts[1] ?? ''; // Ambil kata kedua
                            $this->verifikasiUser($chatId, $email);
                        }
                        else {
                            // Kalau ngetik asal
                            $this->sendMessage($chatId, "❌ Perintah tidak dikenal.\nKetik `/start` untuk bantuan.");
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error("Error Koneksi: " . $e->getMessage());
        }
    }

    // --- 2. FITUR PROFIL (/profile) ---
    private function cekProfil($chatId)
    {
        $data = DB::table('user_telegram')
            ->join('users', 'user_telegram.user_id', '=', 'users.user_id')
            ->join('roles', 'users.role_id', '=', 'roles.role_id')
            ->where('user_telegram.telegram_chat_id', $chatId)
            ->select('users.name', 'users.email', 'roles.name as role_name', 'users.user_id')
            ->first();

        if ($data) {
            // Cari Kandang
            $farms = DB::table('farms')->where('peternak_id', $data->user_id)->pluck('farm_name')->toArray();
            $farmList = empty($farms) ? '-' : implode(', ', $farms);

            $msg = "👤 **PROFIL SAYA**\n\n";
            $msg .= "**Nama:** {$data->name}\n";
            $msg .= "**Email:** {$data->email}\n";
            $msg .= "**Role:** {$data->role_name}\n";
            $msg .= "**Kandang:** {$farmList}\n";
            $msg .= "\n_Status: Terhubung_ ✅";
        } else {
            $msg = "❌ Akun belum terhubung.\n\nSilakan ketik:\n`/cemail email_anda@gmail.com`";
        }

        $this->sendMessage($chatId, $msg);
    }

    // --- 3. FITUR GANTI EMAIL (/cemail) ---
    private function verifikasiUser($chatId, $email)
    {
        // Validasi input kosong
        if (empty($email)) {
            $this->sendMessage($chatId, "⚠️ Format salah!\n\nContoh yang benar:\n`/cemail ahmad@gmail.com`");
            return;
        }

        // Cek User di Database
        $user = DB::table('users')
                ->join('roles', 'users.role_id', '=', 'roles.role_id')
                ->where('email', $email)
                ->select('users.user_id', 'users.name', 'roles.name as role_name')
                ->first();

        // Validasi User Gada
        if (!$user) {
            $this->sendMessage($chatId, "❌ Gagal! Email `$email` tidak ada di database Broilink.");
            return;
        }

        // RESET & SAVE (Logic Ganti Email)
        // Hapus data lama (biar gak double)
        DB::table('user_telegram')->where('telegram_chat_id', $chatId)->delete();
        DB::table('user_telegram')->where('user_id', $user->user_id)->delete();

        // Simpan baru
        DB::table('user_telegram')->insert([
            'user_id' => $user->user_id,
            'telegram_chat_id' => $chatId
        ]);

        $this->sendMessage($chatId, "✅ **BERHASIL TERHUBUNG!**\n\nHalo {$user->name}, akun Anda ({$email}) sudah aktif.\nCek data Anda dengan ketik `/profile`.");
    }

    // --- 4. CEK SENSOR (WASPADA & BAHAYA) ---
    private function checkFarmConditions()
    {
        $farms = DB::table('farms')->get();

        foreach ($farms as $farm) {
            $data = DB::table('iot_data')
                    ->where('farm_id', $farm->farm_id)
                    ->orderBy('timestamp', 'desc')
                    ->first();

            if (!$data) continue;

            // Cek biar gak spam (Cooldown)
            $cacheKey = "alert_sent_" . $data->id;
            if (Cache::has($cacheKey)) continue;

            $config = DB::table('farm_config')
                    ->where('farm_id', $farm->farm_id)
                    ->pluck('value', 'parameter_name')
                    ->toArray();

            $alerts = [];
            $status = 'AMAN';

            // --- Logic Suhu ---
            $t_crit = $config['suhu_kritis_tinggi'] ?? 36;
            $t_warn = $config['suhu_normal_max'] ?? 32;

            if ($data->temperature >= $t_crit) {
                $status = 'BAHAYA';
                $alerts[] = "🔥 Suhu KRITIS: {$data->temperature}°C";
            } elseif ($data->temperature > $t_warn) {
                if ($status != 'BAHAYA') $status = 'WASPADA';
                $alerts[] = "⚠️ Suhu Tinggi: {$data->temperature}°C";
            }

            // --- Logic Amonia ---
            $a_crit = $config['amonia_kritis'] ?? 25;
            $a_warn = $config['amonia_max'] ?? 15;

            if ($data->ammonia >= $a_crit) {
                $status = 'BAHAYA';
                $alerts[] = "☣️ Amonia KRITIS: {$data->ammonia}";
            } elseif ($data->ammonia > $a_warn) {
                if ($status != 'BAHAYA') $status = 'WASPADA';
                $alerts[] = "⚠️ Amonia Tinggi: {$data->ammonia}";
            }

            // Kirim Notif
            if ($status != 'AMAN') {
                $emoji = ($status == 'BAHAYA') ? '🚨' : '⚠️';
                $msg = "{$emoji} **PERINGATAN KANDANG**\n\n";
                $msg .= "Lokasi: {$farm->farm_name}\n";
                $msg .= "Status: **{$status}**\n";
                $msg .= implode("\n", $alerts);

                $userIds = array_filter([$farm->peternak_id, $farm->owner_id]);
                if(!empty($userIds)){
                    $receivers = DB::table('user_telegram')->whereIn('user_id', $userIds)->get();
                    foreach ($receivers as $r) {
                        $this->sendMessage($r->telegram_chat_id, $msg);
                    }
                }
                // Simpan cache biar gak kirim ulang data yg sama (30 menit)
                Cache::put($cacheKey, true, 1800);
            }
        }
    }

    private function sendMessage($chatId, $text)
    {
        try {
            Http::post("https://api.telegram.org/bot{$this->telegramToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown'
            ]);
        } catch (\Exception $e) {}
    }
}
