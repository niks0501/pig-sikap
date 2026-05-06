<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DemoBulkUsersSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->members() as $row) {
            $roleId = Role::where('slug', 'member')->value('id');

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

    private function onlyExistingColumns(string $table, array $attributes): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }
        return collect($attributes)
            ->filter(fn ($value, string $column): bool => Schema::hasColumn($table, $column))
            ->all();
    }

    private function members(): array
    {
        return [
            ['name' => 'Corazon B. Villaruel', 'email' => 'member.corazon@pigsikap.local'],
            ['name' => 'Danilo P. Salazar', 'email' => 'member.danilo@pigsikap.local'],
            ['name' => 'Erlinda S. Gonzales', 'email' => 'member.erlinda@pigsikap.local'],
            ['name' => 'Francisco T. Marasigan', 'email' => 'member.francisco@pigsikap.local'],
            ['name' => 'Gloria M. Dimaculangan', 'email' => 'member.gloria@pigsikap.local'],
            ['name' => 'Hernando R. Castillo', 'email' => 'member.hernando@pigsikap.local'],
            ['name' => 'Imelda C. Bautista', 'email' => 'member.imelda@pigsikap.local'],
            ['name' => 'Josephine L. Abad', 'email' => 'member.josephine@pigsikap.local'],
            ['name' => 'Karen V. Manalo', 'email' => 'member.karen@pigsikap.local'],
            ['name' => 'Leonardo D. Pascual', 'email' => 'member.leonardo@pigsikap.local'],
            ['name' => 'Myrna F. Atienza', 'email' => 'member.myrna@pigsikap.local'],
            ['name' => 'Nelson G. Ocampo', 'email' => 'member.nelson@pigsikap.local'],
            ['name' => 'Ofelia H. Javier', 'email' => 'member.ofelia@pigsikap.local'],
            ['name' => 'Pilar J. Eusebio', 'email' => 'member.pilar@pigsikap.local'],
            ['name' => 'Rodelio K. Yambao', 'email' => 'member.rodelio@pigsikap.local'],
        ];
    }
}
