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

function signatureUser(string $roleSlug): User
{
    $role = Role::query()->where('slug', $roleSlug)->firstOrFail();

    return User::factory()->create([
        'role_id' => $role->id,
        'is_active' => true,
        'must_change_password' => false,
        'email_verified_at' => now(),
    ]);
}

test('preview page includes president name from active user', function () {
    $role = Role::query()->where('slug', 'president')->firstOrFail();
    User::factory()->create([
        'role_id' => $role->id,
        'name' => 'Maria Santos',
        'is_active' => true,
    ]);

    $president = signatureUser('president');

    actingAs($president)
        ->get(route('reports.preview', ['type' => 'monthly', 'date_range' => 'this_month', 'month' => now()->month, 'year' => now()->year, 'include_details' => '1']))
        ->assertOk()
        ->assertSee('Maria Santos');
});

test('preview page includes three signature blocks', function () {
    $presidentRole = Role::query()->where('slug', 'president')->firstOrFail();
    User::factory()->create([
        'role_id' => $presidentRole->id,
        'name' => 'Pedro Cruz',
        'is_active' => true,
    ]);

    $treasurerRole = Role::query()->where('slug', 'treasurer')->firstOrFail();
    User::factory()->create([
        'role_id' => $treasurerRole->id,
        'name' => 'Juan Dela Cruz',
        'is_active' => true,
    ]);

    $president = signatureUser('president');

    actingAs($president)
        ->get(route('reports.preview', ['type' => 'monthly', 'date_range' => 'this_month', 'month' => now()->month, 'year' => now()->year, 'include_details' => '1']))
        ->assertOk()
        ->assertSee('Prepared By')
        ->assertSee('Treasurer')
        ->assertSee('President');
});
