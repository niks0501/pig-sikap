<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function () {
    seed(RoleSeeder::class);
});

function mortalityPolicyPresident(array $overrides = []): User
{
    $role = Role::query()->where('slug', 'president')->firstOrFail();

    return User::factory()->create([
        'role_id' => $role->id,
        'is_active' => true,
        'must_change_password' => false,
        'email_verified_at' => now(),
        ...$overrides,
    ]);
}

test('mortality placeholder routes are disabled', function () {
    expect(Route::has('mortality.index'))->toBeFalse();
    expect(Route::has('mortality.create'))->toBeFalse();
    expect(Route::has('mortality.show'))->toBeFalse();

    $president = mortalityPolicyPresident();

    actingAs($president)->get('/mortality')->assertNotFound();
    actingAs($president)->get('/mortality/create')->assertNotFound();
    actingAs($president)->get('/mortality/1')->assertNotFound();
});

test('sidebar no longer shows mortality navigation entry', function () {
    $president = mortalityPolicyPresident();

    actingAs($president)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Mortality Records');
});
