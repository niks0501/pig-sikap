<?php

/**
 * NavigationConfigTest — Verifies the NavigationService returns correct
 * menu items for each role, and that '*'-wildcard items appear for all.
 */

use App\Models\Role;
use App\Models\User;
use App\Services\NavigationService;

// Helper to create a user with specific role
function navUser(string $slug): User
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

// ── Unit: NavigationService logic ──

test('navigation service returns sections for any user', function () {
    $user = navUser('president');
    $nav = app(NavigationService::class)->forUser($user);

    expect($nav)->toHaveKeys(['colors', 'sections', 'has_quick_actions', 'has_main_items']);
    expect($nav['sections'])->toHaveKeys(['main', 'quick_actions']);
});

test('wildcard items appear for all roles', function () {
    $wildcardItems = ['Dashboard', 'Members'];

    foreach (['president', 'secretary', 'treasurer', 'canvasser', 'caretaker', 'member'] as $slug) {
        $user = navUser($slug);
        $nav = app(NavigationService::class)->forUser($user);

        $labels = collect($nav['sections']['main']['items'])->pluck('label')->toArray();

        foreach ($wildcardItems as $label) {
            expect($labels)->toContain($label);
        }
    }
});

test('president sees all main menu items', function () {
    $user = navUser('president');
    $nav = app(NavigationService::class)->forUser($user);

    $labels = collect($nav['sections']['main']['items'])->pluck('label')->toArray();

    expect($labels)->toContain('Cycles');
    expect($labels)->toContain('Health & Treatments');
    expect($labels)->toContain('Sales Log');
    expect($labels)->toContain('Expenses');
    expect($labels)->toContain('Profitability');
    expect($labels)->toContain('Reports');
    expect($labels)->toContain('Canvassing');
    expect($labels)->toContain('Suppliers');
    expect($labels)->toContain('Penalties');
    expect($labels)->toContain('Audit Trails');
});

test('secretary sees only secretary-relevant items', function () {
    $user = navUser('secretary');
    $nav = app(NavigationService::class)->forUser($user);

    $labels = collect($nav['sections']['main']['items'])->pluck('label')->toArray();

    expect($labels)->toContain('Meetings');
    expect($labels)->toContain('Resolutions');
    expect($labels)->toContain('Reports');
    expect($labels)->toContain('Documents');
    expect($labels)->toContain('Members');
    expect($labels)->toContain('Dashboard');

    // Should NOT see these
    expect($labels)->not->toContain('Cycles');
    expect($labels)->not->toContain('Sales Log');
    expect($labels)->not->toContain('Expenses');
    expect($labels)->not->toContain('Audit Trails');
});

test('treasurer sees only treasurer-relevant items', function () {
    $user = navUser('treasurer');
    $nav = app(NavigationService::class)->forUser($user);

    $labels = collect($nav['sections']['main']['items'])->pluck('label')->toArray();

    expect($labels)->toContain('Sales Log');
    expect($labels)->toContain('Expenses');
    expect($labels)->toContain('Profitability');
    expect($labels)->toContain('Reports');

    // Withdrawals are accessed per-resolution, not a standalone nav item
    expect($labels)->toContain('Resolutions');

    expect($labels)->not->toContain('Cycles');
    expect($labels)->not->toContain('Meetings');
    expect($labels)->not->toContain('Audit Trails');
});

test('canvasser sees canvassing and supplier items', function () {
    $user = navUser('canvasser');
    $nav = app(NavigationService::class)->forUser($user);

    $labels = collect($nav['sections']['main']['items'])->pluck('label')->toArray();

    expect($labels)->toContain('Canvassing');
    expect($labels)->toContain('Suppliers');
    expect($labels)->toContain('Dashboard');
    expect($labels)->toContain('Members');

    expect($labels)->not->toContain('Cycles');
    expect($labels)->not->toContain('Expenses');
    expect($labels)->not->toContain('Audit Trails');
});

test('caretaker sees health and members items', function () {
    $user = navUser('caretaker');
    $nav = app(NavigationService::class)->forUser($user);

    $labels = collect($nav['sections']['main']['items'])->pluck('label')->toArray();

    expect($labels)->toContain('Health & Treatments');
    expect($labels)->toContain('Dashboard');
    expect($labels)->toContain('Members');

    expect($labels)->not->toContain('Cycles');
    expect($labels)->not->toContain('Sales Log');
    expect($labels)->not->toContain('Audit Trails');
});

test('member sees minimal items only', function () {
    $user = navUser('member');
    $nav = app(NavigationService::class)->forUser($user);

    $labels = collect($nav['sections']['main']['items'])->pluck('label')->toArray();

    expect($labels)->toContain('Dashboard');
    expect($labels)->toContain('Members');

    // Should NOT see anything beyond wildcard items
    expect(count($labels))->toBe(2);
});

// ── Quick Actions ──

test('president has all quick actions', function () {
    $user = navUser('president');
    $nav = app(NavigationService::class)->forUser($user);

    $actions = collect($nav['sections']['quick_actions']['items'])->pluck('label')->toArray();

    expect($actions)->toHaveCount(7);
});

test('system_admin sees all main menu items without being explicitly listed', function () {
    $user = navUser('system_admin');
    $nav = app(NavigationService::class)->forUser($user);

    $labels = collect($nav['sections']['main']['items'])->pluck('label')->toArray();

    // system_admin should see everything a president sees, plus Policy Settings
    expect($labels)->toContain('Dashboard');
    expect($labels)->toContain('Cycles');
    expect($labels)->toContain('Health & Treatments');
    expect($labels)->toContain('Sales Log');
    expect($labels)->toContain('Expenses');
    expect($labels)->toContain('Profitability');
    expect($labels)->toContain('Reports');
    expect($labels)->toContain('Meetings');
    expect($labels)->toContain('Canvassing');
    expect($labels)->toContain('Audit Trails');
    expect($labels)->toContain('Policy Settings');
});
