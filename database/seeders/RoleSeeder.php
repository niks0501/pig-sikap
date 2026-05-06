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
                'name' => 'Canvassing/Purchasing Officer',
                'slug' => 'canvasser',
                'description' => 'Handles canvass records, supplier directory, and purchase evidence uploads.',
            ],
            [
                'name' => 'Caretaker/Association Officer',
                'slug' => 'caretaker',
                'description' => 'Manages assigned cycles, health updates, sick and deceased pig reports.',
            ],
            [
                'name' => 'Auditor',
                'slug' => 'auditor',
                'description' => 'Reviews financial records and ensures compliance.',
            ],
            [
                'name' => 'Business Operations Manager',
                'slug' => 'business_ops',
                'description' => 'Oversees sales, buyer relationships, and market operations.',
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
