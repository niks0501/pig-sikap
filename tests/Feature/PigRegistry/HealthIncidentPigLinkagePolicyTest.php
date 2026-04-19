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

function pigLinkagePresident(array $overrides = []): User
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

function makePigLinkageCycle(User $actor, array $overrides = []): PigCycle
{
    return PigCycle::query()->create([
        'batch_code' => 'PLP-'.fake()->unique()->numerify('###'),
        'caretaker_user_id' => $actor->id,
        'cycle_number' => 1,
        'date_of_purchase' => now()->subDays(25)->toDateString(),
        'initial_count' => 10,
        'current_count' => 10,
        'average_weight' => 8.80,
        'stage' => 'Growing',
        'status' => 'Active',
        'has_pig_profiles' => true,
        'notes' => 'Pig linkage policy test',
        'last_reviewed_at' => now(),
        'created_by' => $actor->id,
        ...$overrides,
    ]);
}

function healthIncidentPayload(PigCycle $cycle, array $overrides = []): array
{
    return [
        'cycle_id' => $cycle->id,
        'event_key' => fake()->uuid(),
        'incident_type' => 'isolated',
        'date_reported' => now()->toDateString(),
        'affected_count' => 1,
        ...$overrides,
    ];
}

test('pig specific incidents require pig id when cycle has pig profiles and pig records', function () {
    $president = pigLinkagePresident();
    $cycle = makePigLinkageCycle($president, ['has_pig_profiles' => true]);

    Pig::query()->create([
        'batch_id' => $cycle->id,
        'pig_no' => 1,
        'status' => 'Active',
        'created_by' => $president->id,
    ]);

    actingAs($president)
        ->from(route('health.create', ['cycle_id' => $cycle->id]))
        ->post(route('health.incidents.store'), healthIncidentPayload($cycle, [
            'incident_type' => 'recovered',
            'resolution_target' => 'sick',
            'affected_count' => 1,
        ]))
        ->assertSessionHasErrors(['pig_id']);
});

test('pig specific incidents allow cycle level entry when profiles are disabled', function () {
    $president = pigLinkagePresident();
    $cycle = makePigLinkageCycle($president, ['has_pig_profiles' => false]);

    actingAs($president)
        ->post(route('health.incidents.store'), healthIncidentPayload($cycle, [
            'incident_type' => 'isolated',
            'affected_count' => 2,
        ]))
        ->assertRedirect(route('health.cycles.show', $cycle));

    assertDatabaseHas('cycle_health_incidents', [
        'batch_id' => $cycle->id,
        'incident_type' => 'isolated',
        'pig_id' => null,
        'affected_count' => 2,
    ]);
});

test('pig specific incidents allow temporary cycle level entry when profiles are enabled but no pig records exist', function () {
    $president = pigLinkagePresident();
    $cycle = makePigLinkageCycle($president, ['has_pig_profiles' => true]);

    actingAs($president)
        ->post(route('health.incidents.store'), healthIncidentPayload($cycle, [
            'incident_type' => 'isolated',
            'affected_count' => 2,
        ]))
        ->assertRedirect(route('health.cycles.show', $cycle));

    assertDatabaseHas('cycle_health_incidents', [
        'batch_id' => $cycle->id,
        'incident_type' => 'isolated',
        'pig_id' => null,
        'affected_count' => 2,
    ]);
});

test('pig linked pig specific incidents must have affected count of one', function () {
    $president = pigLinkagePresident();
    $cycle = makePigLinkageCycle($president);

    $pig = Pig::query()->create([
        'batch_id' => $cycle->id,
        'pig_no' => 1,
        'status' => 'Active',
        'created_by' => $president->id,
    ]);

    actingAs($president)
        ->from(route('health.create', ['cycle_id' => $cycle->id]))
        ->post(route('health.incidents.store'), healthIncidentPayload($cycle, [
            'pig_id' => $pig->id,
            'incident_type' => 'recovered',
            'resolution_target' => 'sick',
            'affected_count' => 2,
        ]))
        ->assertSessionHasErrors(['affected_count']);
});

test('pig linked pig specific incidents succeed with affected count of one', function () {
    $president = pigLinkagePresident();
    $cycle = makePigLinkageCycle($president);

    $pig = Pig::query()->create([
        'batch_id' => $cycle->id,
        'pig_no' => 1,
        'status' => 'Active',
        'created_by' => $president->id,
    ]);

    actingAs($president)
        ->post(route('health.incidents.store'), healthIncidentPayload($cycle, [
            'pig_id' => $pig->id,
            'incident_type' => 'isolated',
            'affected_count' => 1,
        ]))
        ->assertRedirect(route('health.cycles.show', $cycle));

    assertDatabaseHas('cycle_health_incidents', [
        'batch_id' => $cycle->id,
        'incident_type' => 'isolated',
        'pig_id' => $pig->id,
        'affected_count' => 1,
    ]);
});

test('pig linked incidents reject pig ids from another cycle', function () {
    $president = pigLinkagePresident();
    $cycle = makePigLinkageCycle($president, ['batch_code' => 'PLP-A']);
    $otherCycle = makePigLinkageCycle($president, ['batch_code' => 'PLP-B']);

    $otherCyclePig = Pig::query()->create([
        'batch_id' => $otherCycle->id,
        'pig_no' => 1,
        'status' => 'Active',
        'created_by' => $president->id,
    ]);

    actingAs($president)
        ->from(route('health.create', ['cycle_id' => $cycle->id]))
        ->post(route('health.incidents.store'), healthIncidentPayload($cycle, [
            'pig_id' => $otherCyclePig->id,
            'incident_type' => 'recovered',
            'resolution_target' => 'sick',
            'affected_count' => 1,
        ]))
        ->assertSessionHasErrors(['pig_id']);
});

