<?php

use App\Models\GeneratedReport;
use App\Models\PigCycle;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;
use function Pest\Laravel\withoutVite;

beforeEach(function () {
    withoutVite();
    seed(RoleSeeder::class);
    Storage::fake('public');
});

function historyUser(string $roleSlug): User
{
    $role = Role::query()->where('slug', $roleSlug)->firstOrFail();

    return User::factory()->create([
        'role_id' => $role->id,
        'is_active' => true,
        'must_change_password' => false,
        'email_verified_at' => now(),
    ]);
}

test('president can access report history', function () {
    actingAs(historyUser('president'))
        ->get(route('reports.history'))
        ->assertOk();
});

test('treasurer can access report history', function () {
    actingAs(historyUser('treasurer'))
        ->get(route('reports.history'))
        ->assertOk();
});

test('secretary can access report history', function () {
    actingAs(historyUser('secretary'))
        ->get(route('reports.history'))
        ->assertOk();
});

test('member cannot access report history', function () {
    actingAs(historyUser('member'))
        ->get(route('reports.history'))
        ->assertForbidden();
});

test('previously generated reports appear on history page', function () {
    $president = historyUser('president');
    $cycle = PigCycle::factory()->create(['status' => 'Active']);

    GeneratedReport::factory()->create([
        'report_type' => 'expense',
        'format' => 'pdf',
        'cycle_id' => $cycle->id,
        'generated_by' => $president->id,
        'status' => 'generated',
        'file_path' => 'generated/reports/test.pdf',
        'generated_at' => now(),
    ]);

    actingAs($president)
        ->get(route('reports.history'))
        ->assertOk()
        ->assertSee('Expense');
});

test('archived reports are hidden from history', function () {
    $president = historyUser('president');

    GeneratedReport::factory()->create([
        'report_type' => 'expense',
        'format' => 'pdf',
        'generated_by' => $president->id,
        'status' => 'archived',
        'generated_at' => now(),
    ]);

    actingAs($president)
        ->get(route('reports.history'))
        ->assertOk();

    expect(GeneratedReport::where('status', 'generated')->count())->toBe(0);
});
