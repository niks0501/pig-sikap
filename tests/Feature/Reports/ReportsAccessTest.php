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

function reportsUser(string $roleSlug): User
{
    $role = Role::query()->where('slug', $roleSlug)->firstOrFail();

    return User::factory()->create([
        'role_id' => $role->id,
        'is_active' => true,
        'must_change_password' => false,
        'email_verified_at' => now(),
    ]);
}

test('reports index is accessible to authorized roles', function () {
    actingAs(reportsUser('president'))->get(route('reports.index'))->assertOk();
    actingAs(reportsUser('treasurer'))->get(route('reports.index'))->assertOk();
    actingAs(reportsUser('secretary'))->get(route('reports.index'))->assertOk();
});

test('reports index is forbidden for members', function () {
    actingAs(reportsUser('member'))->get(route('reports.index'))->assertForbidden();
});

test('president can access all report types', function () {
    $president = reportsUser('president');

    foreach (['inventory', 'health', 'mortality', 'expense', 'sales', 'monthly', 'quarterly', 'profitability'] as $type) {
        actingAs($president)->get(route('reports.generate', ['type' => $type]))->assertOk();
    }
});

test('treasurer can access financial report types only', function () {
    $treasurer = reportsUser('treasurer');

    foreach (['expense', 'sales', 'monthly', 'quarterly', 'profitability'] as $type) {
        actingAs($treasurer)->get(route('reports.generate', ['type' => $type]))->assertOk();
    }

    foreach (['inventory', 'health', 'mortality'] as $type) {
        actingAs($treasurer)->get(route('reports.generate', ['type' => $type]))->assertForbidden();
    }
});

test('secretary can access livestock report types only', function () {
    $secretary = reportsUser('secretary');

    foreach (['inventory', 'health', 'mortality'] as $type) {
        actingAs($secretary)->get(route('reports.generate', ['type' => $type]))->assertOk();
    }

    foreach (['expense', 'sales', 'monthly', 'quarterly', 'profitability'] as $type) {
        actingAs($secretary)->get(route('reports.generate', ['type' => $type]))->assertForbidden();
    }
});
