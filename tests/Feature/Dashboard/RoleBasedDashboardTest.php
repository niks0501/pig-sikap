<?php

/**
 * RoleBasedDashboardTest — Verifies that:
 * 1. Each role is routed to the correct dashboard view
 * 2. Sidebar items are correctly filtered for each role
 * 3. Unauthorized roles cannot access restricted routes
 * 4. Dashboard data endpoint returns correct data per role
 */

use App\Models\Role;
use App\Models\User;

// Helper
function dashUser(string $slug): User
{
    $role = Role::updateOrCreate(
        ['slug' => $slug],
        [
            'name' => ucfirst($slug),
            'description' => "{$slug} role",
        ]
    );

    return User::factory()->create([
        'role_id' => $role->id,
        'is_active' => true,
    ]);
}

// ── Dashboard Access ──

test('president can access dashboard', function () {
    $user = dashUser('president');
    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertOk();
});

test('secretary can access dashboard', function () {
    $user = dashUser('secretary');
    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertOk();
});

test('treasurer can access dashboard', function () {
    $user = dashUser('treasurer');
    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertOk();
});

test('canvasser can access dashboard', function () {
    $user = dashUser('canvasser');
    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertOk();
});

test('caretaker can access dashboard', function () {
    $user = dashUser('caretaker');
    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertOk();
});

test('member can access dashboard', function () {
    $user = dashUser('member');
    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertOk();
});

test('unauthenticated user is redirected from dashboard', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

// ── Dashboard Data API ──

test('dashboard data endpoint works for all roles', function () {
    $slugs = ['president', 'secretary', 'treasurer', 'canvasser', 'caretaker', 'member'];

    foreach ($slugs as $slug) {
        $user = dashUser($slug);
        $response = $this->actingAs($user)->getJson(route('dashboard.data'));
        $response->assertOk();
        // Each role returns at minimum a kpis key
        $response->assertJsonPath('kpis', fn ($kpis) => is_array($kpis));
    }
});

// ── Sidebar Visibility (page-level) ──

test('president sees full sidebar on dashboard page', function () {
    $user = dashUser('president');
    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSee('Cycles');
    $response->assertSee('Sales Log');
    $response->assertSee('Expenses');
    $response->assertSee('Canvassing');
    $response->assertSee('Audit Trails');
});

test('member sees minimal sidebar on dashboard page', function () {
    $user = dashUser('member');
    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSee('Dashboard');

    // Should NOT see restricted items
    $response->assertDontSee('Cycles');
    $response->assertDontSee('Sales Log');
    $response->assertDontSee('Expenses');
    $response->assertDontSee('Audit Trails');
    $response->assertDontSee('Canvassing');
});

test('canvasser sees canvassing and suppliers in sidebar', function () {
    $user = dashUser('canvasser');
    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSee('Canvassing');
    $response->assertSee('Suppliers');
    $response->assertDontSee('Cycles');
    $response->assertDontSee('Audit Trails');
});

test('caretaker sees health in sidebar', function () {
    $user = dashUser('caretaker');
    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSee('Health', false);
    $response->assertDontSee('Cycles');
    $response->assertDontSee('Sales Log');
});

// ── Route Authorization (canvasser specific) ──

test('canvasser can access canvass routes', function () {
    $user = dashUser('canvasser');

    $response = $this->actingAs($user)->get(route('workflow.canvasses.index'));
    $response->assertOk();
});

test('canvasser can access supplier routes', function () {
    $user = dashUser('canvasser');

    $response = $this->actingAs($user)->get(route('workflow.suppliers.index'));
    $response->assertOk();
});

test('canvasser cannot access health routes', function () {
    $user = dashUser('canvasser');

    $response = $this->actingAs($user)->get(route('health.index'));
    $response->assertForbidden();
});

test('canvasser cannot access cycle routes', function () {
    $user = dashUser('canvasser');

    $response = $this->actingAs($user)->get(route('cycles.index'));
    $response->assertForbidden();
});

// ── Route Authorization (caretaker specific) ──

test('caretaker can access health routes', function () {
    $user = dashUser('caretaker');

    $response = $this->actingAs($user)->get(route('health.index'));
    $response->assertOk();
});

test('caretaker cannot access cycle routes', function () {
    $user = dashUser('caretaker');

    $response = $this->actingAs($user)->get(route('cycles.index'));
    $response->assertForbidden();
});

test('caretaker cannot access sales routes', function () {
    $user = dashUser('caretaker');

    $response = $this->actingAs($user)->get(route('sales.index'));
    $response->assertForbidden();
});

test('caretaker cannot access expense routes', function () {
    $user = dashUser('caretaker');

    $response = $this->actingAs($user)->get(route('expenses.index'));
    $response->assertForbidden();
});

// ── Route Authorization (member specific) ──

test('member can access membership page', function () {
    $user = dashUser('member');

    $response = $this->actingAs($user)->get(route('membership.how-to-join'));
    $response->assertOk();
});

test('member cannot access cycles', function () {
    $user = dashUser('member');

    $response = $this->actingAs($user)->get(route('cycles.index'));
    $response->assertForbidden();
});

test('member cannot access audit trails', function () {
    $user = dashUser('member');

    $response = $this->actingAs($user)->get(route('audit-trails.index'));
    $response->assertForbidden();
});

// ── system_admin routing ──

test('system_admin dashboard shows president-level content', function () {
    $user = dashUser('system_admin');

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertOk();

    // System admin should see president-level sidebar (Cycles, Reports, etc.)
    $response->assertSee('Cycles');
    $response->assertSee('Reports');
    $response->assertSee('Audit Trails');
});
