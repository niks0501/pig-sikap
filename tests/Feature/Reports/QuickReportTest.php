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

function quickReportUser(string $roleSlug): User
{
    $role = Role::query()->where('slug', $roleSlug)->firstOrFail();

    return User::factory()->create([
        'role_id' => $role->id,
        'is_active' => true,
        'must_change_password' => false,
        'email_verified_at' => now(),
    ]);
}

test('monthly quick generate redirects to preview with defaults', function () {
    $president = quickReportUser('president');

    $response = actingAs($president)->get(route('reports.quick', ['type' => 'monthly']));

    $response->assertRedirect();
    $redirectUrl = $response->headers->get('Location');
    expect($redirectUrl)->toContain('/reports/monthly/preview');
    expect($redirectUrl)->toContain('date_range=this_month');
    expect($redirectUrl)->toContain('include_details=1');
});

test('quarterly quick generate redirects to preview with defaults', function () {
    $president = quickReportUser('president');

    $response = actingAs($president)->get(route('reports.quick', ['type' => 'quarterly']));

    $response->assertRedirect();
    $redirectUrl = $response->headers->get('Location');
    expect($redirectUrl)->toContain('/reports/quarterly/preview');
    expect($redirectUrl)->toContain('date_range=this_quarter');
    expect($redirectUrl)->toContain('include_details=1');
});

test('dswd-summary quick generate redirects to preview with defaults', function () {
    $president = quickReportUser('president');

    $response = actingAs($president)->get(route('reports.quick', ['type' => 'dswd-summary']));

    $response->assertRedirect();
    $redirectUrl = $response->headers->get('Location');
    expect($redirectUrl)->toContain('/reports/dswd-summary/preview');
    expect($redirectUrl)->toContain('include_details=1');
});

test('per-cycle quick generate redirects with cycle_id', function () {
    $president = quickReportUser('president');

    $response = actingAs($president)->get(route('reports.quick', ['type' => 'per-cycle', 'cycle_id' => 1]));

    $response->assertRedirect();
    $redirectUrl = $response->headers->get('Location');
    expect($redirectUrl)->toContain('/reports/per-cycle/preview');
    expect($redirectUrl)->toContain('cycle_id=1');
    expect($redirectUrl)->toContain('include_details=1');
});

test('quick generate is forbidden for unauthorized roles', function () {
    $member = quickReportUser('member');

    actingAs($member)->get(route('reports.quick', ['type' => 'monthly']))->assertForbidden();
});
