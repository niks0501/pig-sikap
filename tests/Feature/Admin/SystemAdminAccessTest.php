<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('system admin can access admin dashboard', function () {
    $systemAdminRole = Role::where('slug', 'system_admin')->firstOrFail();

    $user = User::factory()->create([
        'role_id' => $systemAdminRole->id,
        'is_active' => true,
        'must_change_password' => false,
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get('/admin/dashboard');

    $response->assertOk();
});

test('non system admin can not access admin dashboard', function () {
    $presidentRole = Role::where('slug', 'president')->firstOrFail();

    $user = User::factory()->create([
        'role_id' => $presidentRole->id,
        'is_active' => true,
        'must_change_password' => false,
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get('/admin/dashboard');

    $response->assertForbidden();
});

test('forced password change blocks access to admin dashboard', function () {
    $systemAdminRole = Role::where('slug', 'system_admin')->firstOrFail();

    $user = User::factory()->create([
        'role_id' => $systemAdminRole->id,
        'is_active' => true,
        'must_change_password' => true,
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get('/admin/dashboard');

    $response->assertRedirect(route('password.force.edit'));
});
