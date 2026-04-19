<?php

use App\Models\PigCycle;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function () {
    seed(RoleSeeder::class);
});

function contractPresident(array $overrides = []): User
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

function makeContractCycle(User $actor, array $overrides = []): PigCycle
{
    return PigCycle::query()->create([
        'batch_code' => 'HTC-'.fake()->unique()->numerify('###'),
        'caretaker_user_id' => $actor->id,
        'cycle_number' => 1,
        'date_of_purchase' => now()->subDays(35)->toDateString(),
        'initial_count' => 12,
        'current_count' => 12,
        'average_weight' => 9.25,
        'stage' => 'Growing',
        'status' => 'Active',
        'has_pig_profiles' => false,
        'notes' => 'Health-to-cycle contract test',
        'last_reviewed_at' => now(),
        'created_by' => $actor->id,
        ...$overrides,
    ]);
}

test('deceased incidents create one operational adjustment through health channel', function () {
    $president = contractPresident();
    $cycle = makeContractCycle($president);
    $eventKey = fake()->uuid();

    Storage::fake('public');

    actingAs($president)
        ->post(route('health.cycles.incidents.store', $cycle), [
            'event_key' => $eventKey,
            'incident_type' => 'deceased',
            'date_reported' => now()->toDateString(),
            'affected_count' => 3,
            'suspected_cause' => 'Contract mortality verification',
            'media' => UploadedFile::fake()->image('contract-deceased.jpg', 1200, 900),
            'source_channel' => 'cycle_timeline',
            'remarks' => 'Contract check',
        ])
        ->assertRedirect(route('health.cycles.show', $cycle));

    $cycle->refresh();

    expect($cycle->current_count)->toBe(9);

    assertDatabaseHas('pig_cycle_adjustments', [
        'batch_id' => $cycle->id,
        'reason' => 'mortality',
        'source_module' => 'health_monitoring',
        'source_type' => 'cycle_health_incident',
        'source_event_key' => $eventKey,
        'quantity_after' => 9,
    ]);
});

test('non deceased incidents remain medical-only and do not change operational count', function () {
    $president = contractPresident();
    $cycle = makeContractCycle($president);
    $eventKey = fake()->uuid();

    actingAs($president)
        ->post(route('health.cycles.incidents.store', $cycle), [
            'event_key' => $eventKey,
            'incident_type' => 'sick',
            'date_reported' => now()->toDateString(),
            'affected_count' => 2,
            'source_channel' => 'cycle_timeline',
            'remarks' => 'Medical event only',
        ])
        ->assertRedirect(route('health.cycles.show', $cycle));

    $cycle->refresh();

    expect($cycle->current_count)->toBe(12);

    assertDatabaseMissing('pig_cycle_adjustments', [
        'batch_id' => $cycle->id,
        'source_event_key' => $eventKey,
    ]);

    assertDatabaseHas('cycle_health_incidents', [
        'batch_id' => $cycle->id,
        'event_key' => $eventKey,
        'incident_type' => 'sick',
    ]);
});


test('recovered incidents remain medical-only and do not change operational count', function () {
    $president = contractPresident();
    $cycle = makeContractCycle($president);
    $eventKey = fake()->uuid();

    $cycle->healthIncidents()->create([
        'event_key' => fake()->uuid(),
        'incident_type' => 'sick',
        'date_reported' => now()->toDateString(),
        'affected_count' => 1,
        'reported_by' => $president->id,
    ]);

    actingAs($president)
        ->post(route('health.cycles.incidents.store', $cycle), [
            'event_key' => $eventKey,
            'incident_type' => 'recovered',
            'resolution_target' => 'sick',
            'date_reported' => now()->toDateString(),
            'affected_count' => 1,
            'source_channel' => 'cycle_timeline',
            'remarks' => 'Recovered after treatment',
        ])
        ->assertRedirect(route('health.cycles.show', $cycle));

    $cycle->refresh();

    expect($cycle->current_count)->toBe(12);

    assertDatabaseMissing('pig_cycle_adjustments', [
        'batch_id' => $cycle->id,
        'source_event_key' => $eventKey,
    ]);

    assertDatabaseHas('cycle_health_incidents', [
        'batch_id' => $cycle->id,
        'event_key' => $eventKey,
        'incident_type' => 'recovered',
        'resolution_target' => 'sick',
    ]);
});

test('recovered incidents require resolution target', function () {
    $president = contractPresident();
    $cycle = makeContractCycle($president);

    $cycle->healthIncidents()->create([
        'event_key' => fake()->uuid(),
        'incident_type' => 'sick',
        'date_reported' => now()->toDateString(),
        'affected_count' => 1,
        'reported_by' => $president->id,
    ]);

    actingAs($president)
        ->from(route('health.cycles.show', $cycle))
        ->post(route('health.cycles.incidents.store', $cycle), [
            'event_key' => fake()->uuid(),
            'incident_type' => 'recovered',
            'date_reported' => now()->toDateString(),
            'affected_count' => 1,
            'source_channel' => 'cycle_timeline',
        ])
        ->assertSessionHasErrors(['resolution_target']);
});

test('recovered incidents cannot resolve beyond unresolved target cases', function () {
    $president = contractPresident();
    $cycle = makeContractCycle($president);

    $cycle->healthIncidents()->create([
        'event_key' => fake()->uuid(),
        'incident_type' => 'sick',
        'date_reported' => now()->toDateString(),
        'affected_count' => 1,
        'reported_by' => $president->id,
    ]);

    actingAs($president)
        ->from(route('health.cycles.show', $cycle))
        ->post(route('health.cycles.incidents.store', $cycle), [
            'event_key' => fake()->uuid(),
            'incident_type' => 'recovered',
            'resolution_target' => 'sick',
            'date_reported' => now()->toDateString(),
            'affected_count' => 2,
            'source_channel' => 'cycle_timeline',
        ])
        ->assertSessionHasErrors(['affected_count']);
});

test('deceased incidents enforce unresolved cap only when target is provided', function () {
    $president = contractPresident();
    $cycle = makeContractCycle($president);

    Storage::fake('public');

    $cycle->healthIncidents()->create([
        'event_key' => fake()->uuid(),
        'incident_type' => 'isolated',
        'date_reported' => now()->toDateString(),
        'affected_count' => 1,
        'reported_by' => $president->id,
    ]);

    actingAs($president)
        ->from(route('health.cycles.show', $cycle))
        ->post(route('health.cycles.incidents.store', $cycle), [
            'event_key' => fake()->uuid(),
            'incident_type' => 'deceased',
            'resolution_target' => 'isolated',
            'date_reported' => now()->toDateString(),
            'affected_count' => 2,
            'suspected_cause' => 'Unresolved cap validation scenario',
            'media' => UploadedFile::fake()->image('contract-deceased-cap.jpg', 1000, 700),
            'source_channel' => 'cycle_timeline',
        ])
        ->assertSessionHasErrors(['affected_count']);
});
