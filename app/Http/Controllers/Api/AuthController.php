<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class AuthController extends Controller
{
    /**
     * ========================================================
     * 1. ALUR REDIRECT (Web / React)
     * ========================================================
     */
    public function redirectToGoogle()
    {
        try {
            return Socialite::driver('google')
                ->stateless()
                ->with(['prompt' => 'select_account'])
                ->redirect();
        } catch (Exception $e) {
            Log::error('Google Redirect Error: ' . $e->getMessage());
            return redirect('http://localhost:3000/login?error=redirect_failed');
        }
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            if (!$googleUser->email) {
                throw new Exception('Email tidak ditemukan dari Google');
            }

            $user = User::updateOrCreate(
                ['email' => $googleUser->email],
                [
                    'google_id'          => $googleUser->id,
                    'name'               => $googleUser->name,
                    'avatar'             => $googleUser->avatar,
                    'login_type'         => 'google',
                    'email_verified_at'  => now(),
                ]
            );

            $token = $user->createToken('web-token')->plainTextToken;

            return redirect()->away('http://localhost:3000/auth/callback?token=' . $token);

        } catch (Exception $e) {
            Log::error('Google Callback Error: ' . $e->getMessage());
            return redirect()->away('http://localhost:3000/login?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * ========================================================
     * 2. ALUR VERIFIKASI TOKEN (Mobile / Flutter - Google)
     * ========================================================
     */
    public function handleMobileGoogleLogin(Request $request)
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        try {
            $client = new \Google\Client();

            $allowedClientIds = [
                env('GOOGLE_CLIENT_ID'),
                env('GOOGLE_ANDROID_CLIENT_ID'),
            ];

            $payload = null;
            foreach ($allowedClientIds as $clientId) {
                $client->setClientId($clientId);
                $payload = $client->verifyIdToken($request->id_token);
                if ($payload) break;
            }

            if (!$payload) {
                Log::warning('Google Mobile Auth: Token tidak valid');
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Token tidak valid atau kadaluarsa'
                ], 401);
            }

            if (empty($payload['email'])) {
                throw new Exception('Email tidak ditemukan dari token Google');
            }

            Log::info('Google User Data: ' . json_encode([
                'email'     => $payload['email'],
                'name'      => $payload['name'] ?? '',
                'google_id' => $payload['sub'],
            ]));

            $user = User::updateOrCreate(
                ['email' => $payload['email']],
                [
                    'google_id'          => $payload['sub'],
                    'name'               => $payload['name'] ?? '',
                    'avatar'             => $payload['picture'] ?? null,
                    'login_type'         => 'google',
                    'email_verified_at'  => now(),
                ]
            );

            $token = $user->createToken('mobile-token')->plainTextToken;

            Log::info('User logged in successfully: ' . $user->email);

            return response()->json([
                'status'       => 'success',
                'message'      => 'Login berhasil',
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'user'         => $user,
            ], 200);

        } catch (Exception $e) {
            Log::error('Google Mobile Auth Error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Autentikasi gagal: ' . $e->getMessage()
            ], 401);
        }
    }

    /**
     * ========================================================
     * 3. LOGIN MANUAL (Email & Password — Khusus Sistem/Admin)
     * ========================================================
     */
    public function handleManualLogin(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        try {
            // Cari user berdasarkan email
            $user = User::where('email', $request->email)->first();

            // Cek: user tidak ditemukan
            if (!$user) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Email atau password salah',
                ], 401);
            }

            // Cek: user ini bukan tipe sistem/manual (misal akun Google tanpa password)
            if (!in_array($user->login_type, ['system', 'manual'])) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Akun ini terdaftar via Google. Silakan login menggunakan Google.',
                ], 403);
            }

            // Cek: password salah
            if (!Hash::check($request->password, $user->password)) {
                Log::warning('Failed manual login attempt for: ' . $request->email);
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Email atau password salah',
                ], 401);
            }

            // Buat token
            $token = $user->createToken('system-token')->plainTextToken;

            Log::info('System user logged in: ' . $user->email);

            return response()->json([
                'status'       => 'success',
                'message'      => 'Login berhasil',
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'user'         => $user,
            ], 200);

        } catch (Exception $e) {
            Log::error('Manual Login Error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan server. Coba beberapa saat lagi.',
            ], 500);
        }
    }

    /**
     * ========================================================
     * 4. FUNGSI PROTECTED (Wajib bawa Bearer Token)
     * ========================================================
     */
    public function user(Request $request)
    {
        $user = $request->user()->load('personalData');

        return response()->json([
            'status' => 'success',
            'data'   => $user
        ]);
    }

    public function logout(Request $request)
    {
        try {
            Log::info('Logging out user: ' . $request->user()->email);
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Berhasil logout'
            ]);
        } catch (Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal logout'
            ], 500);
        }
    }
}