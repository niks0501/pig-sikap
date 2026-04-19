<?php

use App\Models\PigCycle;
use App\Models\Role;
use App\Models\User;
use App\Services\PigRegistry\CycleSummaryService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function () {
    seed(RoleSeeder::class);
});

function idempotencyPresident(array $overrides = []): User
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

function makeIdempotencyCycle(User $actor, array $overrides = []): PigCycle
{
    return PigCycle::query()->create([
        'batch_code' => 'HID-'.fake()->unique()->numerify('###'),
        'caretaker_user_id' => $actor->id,
        'cycle_number' => 1,
        'date_of_purchase' => now()->subDays(30)->toDateString(),
        'initial_count' => 10,
        'current_count' => 10,
        'average_weight' => 9.00,
        'stage' => 'Growing',
        'status' => 'Active',
        'has_pig_profiles' => false,
        'notes' => 'Health incident idempotency test',
        'last_reviewed_at' => now(),
        'created_by' => $actor->id,
        ...$overrides,
    ]);
}

test('module route retries with same event key do not duplicate incident or operational impact', function () {
    $president = idempotencyPresident();
    $cycle = makeIdempotencyCycle($president);
    $eventKey = fake()->uuid();

    $payload = [
        'cycle_id' => $cycle->id,
        'event_key' => $eventKey,
        'incident_type' => 'deceased',
        'date_reported' => now()->toDateString(),
        'affected_count' => 1,
        'source_channel' => 'health_module',
        'remarks' => 'Retried form submission simulation',
    ];

    actingAs($president)
        ->post(route('health.incidents.store'), $payload)
        ->assertRedirect(route('health.cycles.show', $cycle));

    actingAs($president)
        ->post(route('health.incidents.store'), $payload)
        ->assertRedirect(route('health.cycles.show', $cycle));

    $cycle->refresh();

    expect($cycle->current_count)->toBe(9);
    expect($cycle->healthIncidents()->where('event_key', $eventKey)->count())->toBe(1);
    expect($cycle->adjustments()->where('source_event_key', $eventKey)->count())->toBe(1);

    assertDatabaseHas('cycle_health_incidents', [
        'batch_id' => $cycle->id,
        'event_key' => $eventKey,
        'incident_type' => 'deceased',
        'source_channel' => 'health_module',
    ]);
});

test('module route retries with same recovered event key do not duplicate resolution', function () {
    $president = idempotencyPresident();
    $cycle = makeIdempotencyCycle($president);
    $eventKey = fake()->uuid();

    $cycle->healthIncidents()->create([
        'event_key' => fake()->uuid(),
        'incident_type' => 'sick',
        'date_reported' => now()->toDateString(),
        'affected_count' => 2,
        'reported_by' => $president->id,
    ]);

    $payload = [
        'cycle_id' => $cycle->id,
        'event_key' => $eventKey,
        'incident_type' => 'recovered',
        'resolution_target' => 'sick',
        'date_reported' => now()->toDateString(),
        'affected_count' => 1,
        'source_channel' => 'health_module',
        'remarks' => 'Recovered retry simulation',
    ];

    actingAs($president)
        ->post(route('health.incidents.store'), $payload)
        ->assertRedirect(route('health.cycles.show', $cycle));

    actingAs($president)
        ->post(route('health.incidents.store'), $payload)
        ->assertRedirect(route('health.cycles.show', $cycle));

    expect($cycle->healthIncidents()->where('event_key', $eventKey)->count())->toBe(1);
    expect($cycle->adjustments()->where('source_event_key', $eventKey)->count())->toBe(0);

    $summary = app(CycleSummaryService::class)->forCycle($cycle);

    expect($summary['currently_sick'])->toBe(1);
    expect($summary['total_recovered_reported'])->toBe(1);

    assertDatabaseHas('cycle_health_incidents', [
        'batch_id' => $cycle->id,
        'event_key' => $eventKey,
        'incident_type' => 'recovered',
        'resolution_target' => 'sick',
        'source_channel' => 'health_module',
    ]);
});
