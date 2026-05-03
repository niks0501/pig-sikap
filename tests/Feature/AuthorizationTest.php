<?php

/**
 * AuthorizationTest – Verifies only secretary/treasurer/president
 * can access workflow endpoints. Regular members get 403.
 */

use App\Models\Meeting;
use App\Models\User;

// Helper to create a user with a specific role
function createUserWithRole(string $slug): User
{
    $roleId = \DB::table('roles')->insertGetId([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'description' => "{$slug} role",
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return User::factory()->create([
        'role_id' => $roleId,
        'is_active' => true,
    ]);
}

// ── Allowed roles can access endpoints ──

test('secretary can access meetings index', function () {
    $user = createUserWithRole('secretary');

    $response = $this->actingAs($user)->get(route('workflow.meetings.index'));
    $response->assertOk();
});

test('treasurer can access meetings index', function () {
    $user = createUserWithRole('treasurer');

    $response = $this->actingAs($user)->get(route('workflow.meetings.index'));
    $response->assertOk();
});

test('president can access meetings index', function () {
    $user = createUserWithRole('president');

    $response = $this->actingAs($user)->get(route('workflow.meetings.index'));
    $response->assertOk();
});

// ── Blocked roles get 403 ──

test('member role cannot access meetings index', function () {
    $user = createUserWithRole('member');

    $response = $this->actingAs($user)->get(route('workflow.meetings.index'));
    $response->assertForbidden();
});

test('member role cannot create meetings', function () {
    $user = createUserWithRole('member');

    $response = $this->actingAs($user)->get(route('workflow.meetings.create'));
    $response->assertForbidden();
});

test('member role cannot access resolutions index', function () {
    $user = createUserWithRole('member');

    $response = $this->actingAs($user)->get(route('workflow.resolutions.index'));
    $response->assertForbidden();
});

test('member role cannot store a meeting', function () {
    $user = createUserWithRole('member');

    $response = $this->actingAs($user)->postJson(route('workflow.meetings.store'), [
        'title' => 'Test',
        'date' => now()->subDay()->toDateString(),
    ]);

    $response->assertForbidden();
});

test('member role cannot store a resolution', function () {
    $user = createUserWithRole('member');

    $response = $this->actingAs($user)->postJson(route('workflow.resolutions.store'), [
        'meeting_id' => 1,
        'title' => 'Test',
    ]);

    $response->assertForbidden();
});

test('unauthenticated users are redirected to login', function () {
    $response = $this->get(route('workflow.meetings.index'));
    $response->assertRedirect(route('login'));
});
