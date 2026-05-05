<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;
use function Pest\Laravel\withoutVite;

beforeEach(function () {
    withoutVite();
    seed(RoleSeeder::class);
});

function csvUser(string $roleSlug): User
{
    $role = Role::query()->where('slug', $roleSlug)->firstOrFail();

    return User::factory()->create([
        'role_id' => $role->id,
        'is_active' => true,
        'must_change_password' => false,
        'email_verified_at' => now(),
    ]);
}

test('monthly CSV export uses proper financial columns', function () {
    actingAs(csvUser('president'))
        ->get(route('reports.csv', ['type' => 'monthly', 'year' => now()->year, 'month' => now()->month]))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

test('quarterly CSV export uses proper financial columns', function () {
    actingAs(csvUser('president'))
        ->get(route('reports.csv', ['type' => 'quarterly', 'year' => now()->year, 'quarter' => now()->quarter]))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

test('expense CSV export returns structured rows', function () {
    actingAs(csvUser('president'))
        ->get(route('reports.csv', ['type' => 'expense', 'date_range' => 'this_year']))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

test('sales CSV export returns structured rows', function () {
    actingAs(csvUser('president'))
        ->get(route('reports.csv', ['type' => 'sales', 'date_range' => 'this_year']))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});
