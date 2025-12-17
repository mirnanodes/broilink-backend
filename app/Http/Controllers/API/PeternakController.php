<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\IotData;
use App\Models\ManualData;
use App\Services\FarmStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http; // <--- WAJIB ADA
use Illuminate\Support\Str;          // <--- WAJIB ADA
use Carbon\Carbon;

class PeternakController extends Controller
{
    public function __construct(
        private readonly FarmStatusService $statusService
    ) {}

    /**
     * Get peternak dashboard
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $farm = $user->assignedFarm;

        if (!$farm) {
            return response()->json([
                'success' => false,
                'message' => 'Peternak belum ditugaskan ke kandang'
            ], 409);
        }

        $latest = IotData::where('farm_id', $farm->farm_id)
            ->latest('timestamp')
            ->first();

        $config = $this->getFarmConfig($farm->farm_id);

        $current = [
            'temperature' => $latest?->temperature,
            'humidity'    => $latest?->humidity,
            'ammonia'     => $latest?->ammonia,
            'status'      => $this->statusService->determine($latest, $config)
        ];

        $manual = ManualData::where('farm_id', $farm->farm_id)
            ->whereBetween('report_date', [
                now()->subDays(6)->toDateString(),
                now()->toDateString()
            ])
            ->orderBy('report_date')
            ->get();

        $summary = [
            'labels'    => $manual->map(fn ($d) =>
                Carbon::parse($d->report_date)->locale('id')->translatedFormat('D, d M')
            )->toArray(),
            'pakan'     => $manual->map(fn ($d) => (int)round($d->konsumsi_pakan))->toArray(),
            'minum'     => $manual->map(fn ($d) => (int)round($d->konsumsi_air))->toArray(),
            'bobot'     => $manual->map(fn ($d) => (int)round($d->rata_rata_bobot))->toArray(),
            'kematian'  => $manual->map(fn ($d) => (int)$d->jumlah_kematian)->toArray(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'farm' => ['id' => $farm->farm_id, 'name' => $farm->farm_name],
                'current' => $current,
                'summary' => $summary
            ]
        ]);
    }

    /**
     * Submit or update daily manual report
     */
    public function submitReport(Request $request)
    {
        $user = $request->user();
        $farm = $user->assignedFarm;

        if (!$farm) {
            return response()->json(['success' => false, 'message' => 'Peternak belum ditugaskan'], 409);
        }

        $validated = $request->validate([
            'report_date'       => 'required|date',
            'konsumsi_pakan'    => 'required|numeric|min:0',
            'konsumsi_air'      => 'required|numeric|min:0',
            'rata_rata_bobot'   => 'required|numeric|min:0',
            'jumlah_kematian'   => 'required|integer|min:0'
        ]);

        $report = ManualData::updateOrCreate(
            ['farm_id' => $farm->farm_id, 'report_date' => $validated['report_date']],
            [
                'user_id_input'     => $user->user_id,
                'konsumsi_pakan'    => $validated['konsumsi_pakan'],
                'konsumsi_air'      => $validated['konsumsi_air'],
                'rata_rata_bobot'   => $validated['rata_rata_bobot'],
                'jumlah_kematian'   => $validated['jumlah_kematian']
            ]
        );

        return response()->json(['success' => true, 'message' => 'Laporan harian berhasil disimpan', 'data' => $report], 201);
    }

    /**
     * Get peternak profile
     */
    public function getProfile(Request $request)
    {
        $user = $request->user();
        $farm = $user->assignedFarm?->load('owner');

        $owner = null;
        if ($farm && $farm->owner) {
            $owner = ['owner_id' => $farm->owner->user_id, 'owner_name' => $farm->owner->name];
        } elseif ($user->owner_id) {
            $directOwner = $user->directOwner;
            if ($directOwner) {
                $owner = ['owner_id' => $directOwner->user_id, 'owner_name' => $directOwner->name];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'user_id'      => $user->user_id,
                    'username'     => $user->username,
                    'name'         => $user->name,
                    'email'        => $user->email,
                    'phone_number' => $user->phone_number,
                    'profile_pic'  => $user->profile_pic ? Storage::url($user->profile_pic) : null,
                ],
                'farm' => $farm ? ['farm_id' => $farm->farm_id, 'farm_name' => $farm->farm_name] : null,
                'owner' => $owner,
            ]
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'name'          => 'sometimes|required|string',
            'email'         => 'sometimes|email|unique:users,email,' . $user->user_id . ',user_id',
            'phone_number'  => 'sometimes|nullable|string'
        ]);
        $user->update($validated);
        return response()->json(['success' => true, 'message' => 'Profil berhasil diperbarui', 'data' => $user]);
    }

    public function uploadPhoto(Request $request)
    {
        $user = $request->user();
        $request->validate(['photo' => 'required|image|max:2048']);

        if ($user->profile_pic) {
            Storage::delete($user->profile_pic);
        }

        $path = $request->file('photo')->store('public/profiles');
        $user->update(['profile_pic' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Foto profil diperbarui',
            'data' => ['profile_pic' => Storage::url($path)]
        ]);
    }

    // =========================================================================
    // FITUR INTEGRASI TELEGRAM & OTP (TAMBAHAN BARU)
    // =========================================================================

    /**
     * GENERATE LINK TELEGRAM (Deep Link)
     * Frontend memanggil ini -> Dapat URL -> Redirect User ke URL tersebut
     */
    public function getTelegramLink(Request $request)
    {
        $user = $request->user();

        // 1. Buat Token Unik (Tiket)
        $token = Str::random(32);

        // 2. Simpan Tiket di Cache (Berlaku 5 menit)
        Cache::put('tele_connect_' . $token, $user->user_id, 300);

        // 3. Ambil Username Bot dari .env
        $botUsername = env('TELEGRAM_BOT_USERNAME', 'Broilink_bot');

        // 4. Return URL Lengkap
        return response()->json([
            'success' => true,
            'url' => "https://t.me/$botUsername?start=$token",
            'message' => 'Silakan buka link ini untuk menghubungkan akun.'
        ]);
    }

    /**
     * Send OTP (Kirim ke Telegram)
     */
    public function sendOtp(Request $request)
    {
        $user = $request->user();

        // Cek apakah user sudah connect Telegram
        $teleData = DB::table('user_telegram')->where('user_id', $user->user_id)->first();

        if (!$teleData) {
            return response()->json([
                'success' => false,
                'message' => 'Telegram belum terhubung. Silakan hubungkan akun terlebih dahulu.'
            ], 400);
        }

        // Generate OTP
        $otp = rand(100000, 999999);
        Cache::put('otp_' . $user->user_id, $otp, now()->addMinutes(5));

        // Kirim Pesan ke Telegram
        try {
            $token = env('TELEGRAM_BOT_TOKEN');
            $pesan = "🔐 **KODE OTP MASUK**\n\n";
            $pesan .= "Halo {$user->name}, kode OTP Anda adalah:\n";
            $pesan .= "`{$otp}`\n\n";
            $pesan .= "Jangan berikan kode ini ke siapapun.";

            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $teleData->telegram_chat_id,
                'text' => $pesan,
                'parse_mode' => 'Markdown'
            ]);
        } catch (\Exception $e) {
            // Silent fail
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP berhasil dikirim ke Telegram',
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate(['otp' => 'required|digits:6']);
        $cachedOtp = Cache::get('otp_' . $user->user_id);

        if (!$cachedOtp || $cachedOtp != $validated['otp']) {
            return response()->json(['success' => false, 'message' => 'OTP tidak valid atau kedaluwarsa'], 400);
        }

        Cache::forget('otp_' . $user->user_id);
        return response()->json(['success' => true, 'message' => 'OTP berhasil diverifikasi']);
    }

    /**
     * Helper: get farm config
     */
    private function getFarmConfig(int $farmId): array
    {
        return DB::table('farm_config')
            ->where('farm_id', $farmId)
            ->pluck('value', 'parameter_name')
            ->toArray();
    }
}
