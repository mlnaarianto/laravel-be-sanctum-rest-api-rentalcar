<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Buat akun admin sistem
        $admin = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'login_type' => 'system',
                'email_verified_at' => now(),
            ]
        );

        // Buat personal data admin
        $admin->personalData()->updateOrCreate(
            ['user_id' => $admin->id],
            [
                'phone' => '081234567890',
                'address' => 'Batam, Kepulauan Riau',
            ]
        );

        // Jika pakai Spatie nanti
        // $admin->assignRole('Super Admin');
    }
}