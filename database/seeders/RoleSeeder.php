<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed the application's roles.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'System Administrator',
                'slug' => 'system_admin',
                'description' => 'Technical superuser responsible for account and permission management.',
            ],
            [
                'name' => 'President',
                'slug' => 'president',
                'description' => 'Highest operational officer for approvals and decision-making.',
            ],
            [
                'name' => 'Secretary',
                'slug' => 'secretary',
                'description' => 'Responsible for records, minutes, and document management.',
            ],
            [
                'name' => 'Treasurer',
                'slug' => 'treasurer',
                'description' => 'Handles financial records and fund monitoring.',
            ],
            [
                'name' => 'Officer',
                'slug' => 'officer',
                'description' => 'Operational support role for day-to-day updates.',
            ],
            [
                'name' => 'Member',
                'slug' => 'member',
                'description' => 'General member role with limited access.',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['slug' => $role['slug']],
                [
                    'name' => $role['name'],
                    'description' => $role['description'],
                ]
            );
        }
    }
}
