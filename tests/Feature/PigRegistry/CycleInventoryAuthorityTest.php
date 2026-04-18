<?php

use App\Models\Pig;
use App\Models\PigCycle;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function () {
    seed(RoleSeeder::class);
});

function inventoryPresident(array $overrides = []): User
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

function makeInventoryCycle(User $actor, array $overrides = []): PigCycle
{
    return PigCycle::query()->create([
        'batch_code' => 'CIA-'.fake()->unique()->numerify('###'),
        'caretaker_user_id' => $actor->id,
        'cycle_number' => 1,
        'date_of_purchase' => now()->subDays(10)->toDateString(),
        'initial_count' => 10,
        'current_count' => 10,
        'average_weight' => 7.50,
        'stage' => 'Growing',
        'status' => 'Active',
        'has_pig_profiles' => false,
        'notes' => 'Cycle inventory authority test',
        'last_reviewed_at' => now(),
        'created_by' => $actor->id,
        ...$overrides,
    ]);
}

test('deceased incident retries do not double-apply inventory deductions', function () {
    $president = inventoryPresident();
    $cycle = makeInventoryCycle($president);

    $eventKey = fake()->uuid();

    $payload = [
        'event_key' => $eventKey,
        'incident_type' => 'deceased',
        'date_reported' => now()->toDateString(),
        'affected_count' => 2,
        'remarks' => 'Initial mortality event',
    ];

    actingAs($president)
        ->post(route('health.cycles.incidents.store', $cycle), $payload)
        ->assertRedirect(route('health.cycles.show', $cycle));

    actingAs($president)
        ->post(route('health.cycles.incidents.store', $cycle), $payload)
        ->assertRedirect(route('health.cycles.show', $cycle));

    $cycle->refresh();

    expect($cycle->current_count)->toBe(8);
    expect($cycle->healthIncidents()->where('event_key', $eventKey)->count())->toBe(1);
    expect($cycle->adjustments()->where('source_event_key', $eventKey)->count())->toBe(1);

    assertDatabaseHas('pig_cycle_adjustments', [
        'batch_id' => $cycle->id,
        'source_event_key' => $eventKey,
        'source_module' => 'health_monitoring',
        'reason' => 'mortality',
        'quantity_after' => 8,
    ]);
});

test('pig profile status effects are persisted through the adjustment ledger', function () {
    $president = inventoryPresident();
    $cycle = makeInventoryCycle($president, [
        'current_count' => 3,
        'initial_count' => 3,
    ]);

    actingAs($president)
        ->post(route('cycles.profiles.store', $cycle), [
            'pig_no' => 1,
            'status' => 'Deceased',
        ])
        ->assertRedirect(route('cycles.show', $cycle));

    $cycle->refresh();

    expect($cycle->current_count)->toBe(2);

    $pig = Pig::query()->where('batch_id', $cycle->id)->where('pig_no', 1)->firstOrFail();

    assertDatabaseHas('pig_cycle_adjustments', [
        'batch_id' => $cycle->id,
        'source_module' => 'pig_registry',
        'source_type' => 'pig_profile_create',
        'source_id' => $pig->id,
        'reason' => 'mortality',
        'quantity_after' => 2,
    ]);
});
