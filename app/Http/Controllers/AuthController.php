<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class AuthController extends Controller
{
    /**
     * Redirect ke Google untuk login
     */
    public function redirectToGoogle()
    {
        try {
            Log::info('Redirecting to Google OAuth');

            return Socialite::driver('google')
                ->with(['prompt' => 'select_account'])
                ->redirect();
        } catch (Exception $e) {
            Log::error('Google Redirect Error: ' . $e->getMessage());

            return redirect('http://localhost:3000?login=error&message=' . urlencode('Gagal redirect ke Google: ' . $e->getMessage()));
        }
    }

    /**
     * Handle callback dari Google
     */
    public function handleGoogleCallback()
    {
        try {
            Log::info('Google Callback Received');

            // Ambil data user dari Google
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->user();

            Log::info('Google User Data:', [
                'email' => $googleUser->email,
                'name' => $googleUser->name,
                'google_id' => $googleUser->id
            ]);

            // Validasi data dari Google
            if (!$googleUser->email) {
                throw new Exception('Email tidak ditemukan dari Google');
            }

            // Cek user berdasarkan google_id atau email
            $user = User::where('google_id', $googleUser->id)
                ->orWhere('email', $googleUser->email)
                ->first();

            if ($user) {
                // Update data user yang sudah ada
                Log::info('Updating existing user: ' . $user->email);

                $user->update([
                    'google_id' => $googleUser->id,
                    'name' => $googleUser->name,
                    'avatar' => $googleUser->avatar,
                    'email' => $googleUser->email,
                    'email_verified_at' => now(),
                ]);
            } else {
                // Buat user baru
                Log::info('Creating new user: ' . $googleUser->email);

                $user = User::create([
                    'google_id' => $googleUser->id,
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'avatar' => $googleUser->avatar,
                    'email_verified_at' => now(),
                ]);
            }

            // Login user
            Auth::login($user, true);

            Log::info('User logged in successfully: ' . $user->email);

            // Regenerate session untuk keamanan
            session()->regenerate();

            // ✅ FIX: Gunakan hash fragment (#) bukan query parameter
            return redirect()->away('http://localhost:3000/login#success');
        } catch (Exception $e) {
            Log::error('Google Callback Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return redirect()->away('http://localhost:3000/login?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Ambil data user yang sedang login
     */
    public function user(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User tidak ditemukan atau tidak login'
                ], 401);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $user->id,
                    'google_id' => $user->google_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Get User Error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data user'
            ], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            if ($user) {
                Log::info('Logging out user: ' . $user->email);

                Auth::guard('web')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Berhasil logout'
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada user yang login'
            ], 400);
        } catch (Exception $e) {
            Log::error('Logout Error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal logout'
            ], 500);
        }
    }

    /**
     * Cek status login
     */
    public function checkStatus(Request $request)
    {
        try {
            $user = $request->user();

            if ($user) {
                return response()->json([
                    'status' => 'authenticated',
                    'user' => [
                        'id' => $user->id,
                        'google_id' => $user->google_id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => $user->avatar,
                    ]
                ]);
            }

            return response()->json([
                'status' => 'unauthenticated'
            ]);
        } catch (Exception $e) {
            Log::error('Check Status Error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengecek status login'
            ], 500);
        }
    }
}
