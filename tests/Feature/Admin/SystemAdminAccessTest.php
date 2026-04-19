<?php

use App\Models\Role;
use App\Models\AuditTrail;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function () {
    seed(RoleSeeder::class);
});

test('system admin can access admin dashboard', function () {
    $systemAdminRole = Role::where('slug', 'system_admin')->firstOrFail();

    $user = User::factory()->create([
        'role_id' => $systemAdminRole->id,
        'is_active' => true,
        'must_change_password' => false,
        'email_verified_at' => now(),
    ]);

    $response = actingAs($user)->get('/admin/dashboard');

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

    $response = actingAs($user)->get('/admin/dashboard');

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

    $response = actingAs($user)->get('/admin/dashboard');

    $response->assertRedirect(route('password.force.edit'));
});

test('system admin can view activity log context for module changes', function () {
    $systemAdminRole = Role::where('slug', 'system_admin')->firstOrFail();

    $admin = User::factory()->create([
        'role_id' => $systemAdminRole->id,
        'is_active' => true,
        'must_change_password' => false,
        'email_verified_at' => now(),
    ]);

    AuditTrail::query()->create([
        'user_id' => $admin->id,
        'action' => 'cycle_health_incident_recorded',
        'module' => 'health_monitoring',
        'description' => 'Recorded recovered incident for cycle HC-101 affecting 1 pig.',
        'context_json' => [
            'cycle_id' => 101,
            'cycle_batch_code' => 'HC-101',
            'incident_id' => 501,
            'incident_type' => 'recovered',
            'resolution_target' => 'sick',
            'affected_count' => 1,
        ],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
    ]);

    $response = actingAs($admin)->getJson(route('admin.activity-logs.index'));

    $response->assertOk();
    $response->assertJsonPath('data.0.action', 'cycle_health_incident_recorded');
    $response->assertJsonPath('data.0.context_json.incident_type', 'recovered');
    $response->assertJsonPath('data.0.context_json.resolution_target', 'sick');
    $response->assertJsonPath('data.0.reference', 'Cycle HC-101 • Incident #501');
});
