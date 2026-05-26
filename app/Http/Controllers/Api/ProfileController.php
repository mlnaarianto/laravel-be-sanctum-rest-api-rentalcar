<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'ktp' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        try {
            // ==========================================
            // 2. UPDATE TABEL USERS (Name & Avatar)
            // ==========================================
            $userData = ['name' => $request->name];

            if ($request->hasFile('avatar')) {
                // Hapus avatar lama jika ada & bukan dari Google
                if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
                    // Cara lebih aman mengambil path: '/storage/avatars/xxx.jpg' -> 'avatars/xxx.jpg'
                    $oldPath = str_replace('/storage/', '', parse_url($user->avatar, PHP_URL_PATH));
                    Storage::disk('public')->delete($oldPath);
                }

                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $userData['avatar'] = asset('storage/' . $avatarPath);
            }

            $user->update($userData);

            // ==========================================
            // 3. UPDATE TABEL PERSONAL_DATA (Phone, Birth Date, KTP)
            // ==========================================
            $personalDataPayload = [
                'phone' => $request->phone,
                'birth_date' => $request->birth_date,
            ];

            if ($request->hasFile('ktp')) {
                $personalData = $user->personalData;
                
                // Hapus KTP lama jika ada
                if ($personalData && $personalData->ktp_image) {
                    $oldKtpPath = str_replace('/storage/', '', parse_url($personalData->ktp_image, PHP_URL_PATH));
                    Storage::disk('public')->delete($oldKtpPath);
                }

                $ktpPath = $request->file('ktp')->store('ktp_images', 'public');
                $personalDataPayload['ktp_image'] = asset('storage/' . $ktpPath);
            }

            $user->personalData()->updateOrCreate(
                ['user_id' => $user->id],
                $personalDataPayload
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Profil berhasil diperbarui',
                'data' => $user->fresh()->load('personalData') 
            ], 200);

        } catch (\Exception $e) {
            Log::error('Update Profile Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui profil: ' . $e->getMessage()
            ], 500);
        }
    }
}