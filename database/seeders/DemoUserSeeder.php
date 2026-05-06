<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        if (Role::count() === 0) {
            $this->call(RoleSeeder::class);
        }

        DB::transaction(function (): void {
            $this->seedUsers();
        });
    }

    private function seedUsers(): void
    {
        foreach ($this->users() as $row) {
            $roleId = Role::where('slug', $row['role'])->value('id');

            $user = User::updateOrCreate(
                ['email' => $row['email']],
                $this->onlyExistingColumns('users', [
                    'name' => $row['name'],
                    'password' => Hash::make('password'),
                    'role_id' => $roleId,
                    'is_active' => true,
                    'must_change_password' => false,
                ])
            );

            if (Schema::hasColumn('users', 'email_verified_at')) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
        }
    }

    /**
     * Keep this seeder resilient if migrations are adjusted during development.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function onlyExistingColumns(string $table, array $attributes): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        return collect($attributes)
            ->filter(fn ($value, string $column): bool => Schema::hasColumn($table, $column))
            ->all();
    }

    /**
     * @return list<array<string, string>>
     */
    private function users(): array
    {
        return [
            [
                'name' => 'Eva G. Vivas',
                'email' => 'president.eva@pigsikap.local',
                'role' => 'president',
            ],
            [
                'name' => 'Ronalyn C. Balbar',
                'email' => 'secretary.ronalyn@pigsikap.local',
                'role' => 'secretary',
            ],
            [
                'name' => 'Anaceta C. Guevarra',
                'email' => 'treasurer.anaceta@pigsikap.local',
                'role' => 'treasurer',
            ],
            [
                'name' => 'Maricon T. Aquino',
                'email' => 'officer.maricon@pigsikap.local',
                'role' => 'caretaker',
            ],
            [
                'name' => 'Pedro S. Santos',
                'email' => 'canvasser.pedro@pigsikap.local',
                'role' => 'canvasser',
            ],
            [
                'name' => 'Juan R. Dela Cruz',
                'email' => 'auditor.juan@pigsikap.local',
                'role' => 'auditor',
            ],
            [
                'name' => 'Maria L. Reyes',
                'email' => 'operations.maria@pigsikap.local',
                'role' => 'business_ops',
            ],
            [
                'name' => 'Leciria T. Vabingan',
                'email' => 'member.leciria@pigsikap.local',
                'role' => 'member',
            ],
            [
                'name' => 'Antonio M. Garcia',
                'email' => 'member.antonio@pigsikap.local',
                'role' => 'member',
            ],
            [
                'name' => 'Josefina P. Ramos',
                'email' => 'member.josefina@pigsikap.local',
                'role' => 'member',
            ],
            [
                'name' => 'Roberto N. Villanueva',
                'email' => 'member.roberto@pigsikap.local',
                'role' => 'member',
            ],
            [
                'name' => 'Teresa C. Mendoza',
                'email' => 'member.teresa@pigsikap.local',
                'role' => 'member',
            ],
            [
                'name' => 'Rodrigo A. Santos',
                'email' => 'member.rodrigo@pigsikap.local',
                'role' => 'member',
            ],
            [
                'name' => 'Elena B. Torres',
                'email' => 'member.elena@pigsikap.local',
                'role' => 'member',
            ],
            [
                'name' => 'Luis F. Domingo',
                'email' => 'member.luis@pigsikap.local',
                'role' => 'member',
            ],
            [
                'name' => 'Felicidad R. Mercado',
                'email' => 'caretaker.cycle3@pigsikap.local',
                'role' => 'member',
            ],
            [
                'name' => 'Domingo C. Cruz',
                'email' => 'caretaker.cycle4@pigsikap.local',
                'role' => 'member',
            ],
            [
                'name' => 'Rosario P. Lopez',
                'email' => 'caretaker.cycle5@pigsikap.local',
                'role' => 'member',
            ],
        ];
    }
}
