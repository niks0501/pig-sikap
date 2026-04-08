<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SystemAdminSeeder extends Seeder
{
    /**
     * Seed the default system administrator account.
     */
    public function run(): void
    {
        $role = Role::where('slug', 'system_admin')->first();

        if (! $role) {
            return;
        }

        User::updateOrCreate(
            ['email' => env('SYSTEM_ADMIN_EMAIL', 'admin@pigsikap.local')],
            [
                'name' => env('SYSTEM_ADMIN_NAME', 'System Admin'),
                'password' => Hash::make(env('SYSTEM_ADMIN_PASSWORD')),
                'role_id' => $role->id,
                'is_active' => true,
                'must_change_password' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
