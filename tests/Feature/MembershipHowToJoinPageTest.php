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

test('membership how to join page loads for authenticated users', function () {
    $role = Role::query()->where('slug', 'president')->firstOrFail();
    $user = User::factory()->create([
        'role_id' => $role->id,
        'is_active' => true,
        'must_change_password' => false,
        'email_verified_at' => now(),
    ]);

    actingAs($user)
        ->get(route('membership.how-to-join'))
        ->assertOk()
        ->assertSee('How to Join Elite Visionaries Association')
        ->assertSee('Photocopy of Valid ID')
        ->assertSee('DSWD SLP Profiling');
});
