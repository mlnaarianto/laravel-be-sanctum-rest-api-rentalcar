<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\Role as RoleEnum;
use App\Enums\Permission as PermissionEnum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Reset cache Spatie agar tidak membaca data lama saat proses seeding
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Buat semua Permissions berdasarkan Enum
        foreach (PermissionEnum::cases() as $permissionEnum) {
            Permission::firstOrCreate([
                'name' => $permissionEnum->value,
                'guard_name' => 'web'
            ]);
        }

        // 3. Buat semua Roles berdasarkan Enum & Sinkronisasi Permissions-nya
        foreach (RoleEnum::cases() as $roleEnum) {
            $role = Role::firstOrCreate([
                'name' => $roleEnum->value,
                'guard_name' => 'web'
            ]);

            // Ambil daftar permission dari fungsi permissions() di Enum Role
            // Lalu ekstrak string value-nya
            $permissionsForRole = collect($roleEnum->permissions())
                ->map(fn($perm) => $perm->value)
                ->toArray();

            // Pasangkan permission ke role tersebut
            $role->syncPermissions($permissionsForRole);
        }

        // 4. Buat akun admin sistem
        $admin = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'login_type' => 'system',
                'email_verified_at' => now(),
            ]
        );

        // 5. Buat personal data admin
        $admin->personalData()->updateOrCreate(
            ['user_id' => $admin->id],
            [
                'phone' => '081234567890',
                'address' => 'Batam, Kepulauan Riau',
            ]
        );

        // 6. Berikan role Super Admin ke user tersebut (menggunakan value Enum agar anti-typo)
        $admin->assignRole(RoleEnum::SuperAdmin->value);
    }
}