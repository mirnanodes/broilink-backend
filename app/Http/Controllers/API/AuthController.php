<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\RequestLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Handle user login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        $user = User::where('username', $credentials['username'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Update last login
        $user->last_login = now();
        $user->save();

        // Create token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'user_id' => $user->user_id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role ? $user->role->name : null,
                'role_id' => $user->role_id
            ]
        ], 200);
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ], 200);
    }

    /**
     * Handle forgot password request
     * Checks if email exists and creates request log for admin
     */
    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email'
        ]);

        // Check if email exists in database
        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Akun tidak ditemukan'
            ], 404);
        }

        // Create request log for admin
        $requestLog = RequestLog::create([
            'user_id' => $user->user_id,
            'sender_name' => $user->name,
            'phone_number' => $user->phone_number,
            'request_type' => $user->role ? $user->role->name : 'User',
            'request_content' => "Nomor WhatsApp: {$user->phone_number}\nDetail Permintaan: Lupa Password",
            'status' => 'pending',
            'sent_time' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permintaan berhasil dikirim. Admin akan menghubungi Anda melalui WhatsApp.',
            'request_id' => $requestLog->request_id
        ], 200);
    }

    /**
     * Handle guest report for account issues
     * Creates request log with guest information
     */
    public function guestReport(Request $request)
    {
        $validated = $request->validate([
            'whatsapp' => 'required|string',
            'email' => 'required|email',
            'problem_type' => 'required|string|in:Menunggu Detail Login,Masalah Data,Lainnya'
        ]);

        // Create guest request log
        $requestLog = RequestLog::create([
            'user_id' => 0, // Guest user_id is always 0
            'sender_name' => 'Guest',
            'phone_number' => $validated['whatsapp'],
            'request_type' => 'Guest',
            'request_content' => "Email: {$validated['email']}\nNomor WhatsApp: {$validated['whatsapp']}\nDetail Permintaan: {$validated['problem_type']}",
            'status' => 'pending',
            'sent_time' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil terkirim. Admin akan menghubungi Anda segera.',
            'request_id' => $requestLog->request_id
        ], 200);
    }
}
