<?php

use App\Models\AuditTrail;
use App\Models\Pig;
use App\Models\PigBatch;
use App\Models\PigBatchAdjustment;
use App\Models\PigBatchStatusHistory;
use App\Models\PigCycle;
use App\Models\PigBreeder;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function () {
    seed(RoleSeeder::class);
});

function pigRegistryNextCode(string $prefix = 'B'): string
{
    static $counter = 100;

    $counter++;

    return $prefix.'-'.$counter;
}

function pigRegistryUser(string $roleSlug, array $overrides = []): User
{
    $role = Role::query()->where('slug', $roleSlug)->firstOrFail();

    return User::factory()->create([
        'role_id' => $role->id,
        'is_active' => true,
        'must_change_password' => false,
        'email_verified_at' => now(),
        ...$overrides,
    ]);
}

function presidentUser(array $overrides = []): User
{
    return pigRegistryUser('president', $overrides);
}

function secretaryUser(array $overrides = []): User
{
    return pigRegistryUser('secretary', $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 */
function makeBreeder(User $actor, array $overrides = []): PigBreeder
{
    return PigBreeder::query()->create([
        'breeder_code' => pigRegistryNextCode('INA'),
        'name_or_tag' => 'Inahin '.pigRegistryNextCode('TAG'),
        'reproductive_status' => 'Active',
        'created_by' => $actor->id,
        ...$overrides,
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 */
function makeBatch(User $actor, array $overrides = []): PigBatch
{
    return PigBatch::query()->create([
        'batch_code' => pigRegistryNextCode('B'),
        'breeder_id' => null,
        'caretaker_user_id' => $actor->id,
        'cycle_number' => 1,
        'date_of_purchase' => now()->toDateString(),
        'initial_count' => 10,
        'current_count' => 10,
        'average_weight' => 3.50,
        'stage' => 'Piglet',
        'status' => 'Active',
        'has_pig_profiles' => false,
        'notes' => 'Test batch record',
        'last_reviewed_at' => now(),
        'created_by' => $actor->id,
        ...$overrides,
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function batchPayload(array $overrides = []): array
{
    return [
        'batch_code' => pigRegistryNextCode('B'),
        'breeder_id' => null,
        'caretaker_user_id' => null,
        'cycle_number' => 3,
        'date_of_purchase' => now()->toDateString(),
        'initial_count' => 6,
        'average_weight' => 3.25,
        'stage' => 'Piglet',
        'status' => 'Active',
        'has_pig_profiles' => false,
        'notes' => 'Initial registration test.',
        ...$overrides,
    ];
}

function batchCodesFromResponse(array $responseData): Collection
{
    return collect($responseData)->pluck('batch_code');
}

test('guest is redirected to login for pig registry pages', function () {
    $president = presidentUser();
    $batch = makeBatch($president);

    get(route('cycles.index'))->assertRedirect(route('login'));
    get(route('cycles.create'))->assertRedirect(route('login'));
    get(route('cycles.show', $batch))->assertRedirect(route('login'));
    get(route('breeders.create'))->assertRedirect(route('login'));
});

test('non president is forbidden from all pig registry actions', function () {
    $president = presidentUser();
    $secretary = secretaryUser();
    $batch = makeBatch($president);
    $pig = Pig::query()->create([
        'batch_id' => $batch->id,
        'pig_no' => 1,
        'status' => 'Active',
        'created_by' => $president->id,
    ]);

    actingAs($secretary)->get(route('cycles.index'))->assertForbidden();
    actingAs($secretary)->get(route('cycles.create'))->assertForbidden();
    actingAs($secretary)->get(route('cycles.archived'))->assertForbidden();
    actingAs($secretary)->get(route('cycles.show', $batch))->assertForbidden();
    actingAs($secretary)->get(route('cycles.edit', $batch))->assertForbidden();
    actingAs($secretary)->get(route('cycles.profiles.index', $batch))->assertForbidden();
    actingAs($secretary)->get(route('breeders.create'))->assertForbidden();

    actingAs($secretary)->post(route('cycles.store'), batchPayload())->assertForbidden();
    actingAs($secretary)->put(route('cycles.update', $batch), [
        'stage' => 'Piglet',
        'status' => 'Active',
    ])->assertForbidden();
    actingAs($secretary)->delete(route('cycles.destroy', $batch))->assertForbidden();
    actingAs($secretary)->patch(route('cycles.archive', $batch))->assertForbidden();

    actingAs($secretary)->post(route('cycles.profiles.store', $batch), [
        'pig_no' => 2,
        'status' => 'Active',
    ])->assertForbidden();

    actingAs($secretary)->put(route('cycles.profiles.update', [$batch, $pig]), [
        'pig_no' => 1,
        'status' => 'Active',
    ])->assertForbidden();
    actingAs($secretary)->delete(route('cycles.profiles.destroy', [$batch, $pig]))->assertForbidden();

    actingAs($secretary)->post(route('cycles.adjustments.store', $batch), [
        'adjustment_type' => 'increase',
        'quantity_change' => 1,
        'reason' => 'recount',
    ])->assertForbidden();

    actingAs($secretary)->post(route('cycles.status.store', $batch), [
        'new_stage' => 'Weaning',
    ])->assertForbidden();

    actingAs($secretary)->post(route('breeders.store'), [
        'breeder_code' => pigRegistryNextCode('INA'),
        'name_or_tag' => 'Forbidden breeder',
        'reproductive_status' => 'Active',
    ])->assertForbidden();
});

test('president can view pig registry html and json payload', function () {
    $president = presidentUser();
    $batch = makeBatch($president);

    actingAs($president)
        ->get(route('cycles.index'))
        ->assertOk()
        ->assertViewIs('cycles.index')
        ->assertViewHasAll(['cycles', 'filters', 'summary', 'recentUpdates']);

    $jsonResponse = actingAs($president)->getJson(route('cycles.index'));

    $jsonResponse
        ->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            'summary' => [
                'active_cycles',
                'total_piglets',
                'total_fatteners',
                'total_sick',
                'total_deceased',
                'ready_for_sale_cycles',
            ],
            'recent_updates',
        ]);

    expect(batchCodesFromResponse($jsonResponse->json('data') ?? [])->all())->toContain($batch->batch_code);
});

test('pig registry json endpoint filters by search stage status breeder caretaker and scope', function () {
    $president = presidentUser();

    $targetBreeder = makeBreeder($president, ['breeder_code' => 'INA-TARGET', 'name_or_tag' => 'Target Breeder']);
    $otherBreeder = makeBreeder($president, ['breeder_code' => 'INA-OTHER', 'name_or_tag' => 'Other Breeder']);

    $targetCaretaker = presidentUser(['name' => 'Target Caretaker']);
    $otherCaretaker = presidentUser(['name' => 'Other Caretaker']);

    $targetBatch = makeBatch($president, [
        'batch_code' => 'B-TARGET-001',
        'breeder_id' => $targetBreeder->id,
        'caretaker_user_id' => $targetCaretaker->id,
        'stage' => 'Piglet',
        'status' => 'Active',
    ]);

    makeBatch($president, [
        'batch_code' => 'B-OTHER-002',
        'breeder_id' => $otherBreeder->id,
        'caretaker_user_id' => $otherCaretaker->id,
        'stage' => 'Fattening',
        'status' => 'Under Monitoring',
    ]);

    $response = actingAs($president)->getJson(route('cycles.index', [
        'search' => 'TARGET',
        'stage' => 'Piglet',
        'status' => 'Active',
        'breeder' => (string) $targetBreeder->id,
        'caretaker' => (string) $targetCaretaker->id,
        'scope' => 'active',
    ]));

    $codes = batchCodesFromResponse($response->json('data') ?? [])->all();

    expect($codes)->toHaveCount(1);
    expect($codes)->toContain($targetBatch->batch_code);
});

test('pig registry scope filter separates active and archived records in json', function () {
    $president = presidentUser();
    $activeBatch = makeBatch($president, ['status' => 'Active', 'stage' => 'Piglet']);
    $archivedBatch = makeBatch($president, ['status' => 'Closed', 'stage' => 'Completed']);

    $activeResponse = actingAs($president)->getJson(route('cycles.index', ['scope' => 'active']));
    $activeCodes = batchCodesFromResponse($activeResponse->json('data') ?? [])->all();

    expect($activeCodes)->toContain($activeBatch->batch_code);
    expect($activeCodes)->not->toContain($archivedBatch->batch_code);

    $archivedResponse = actingAs($president)->getJson(route('cycles.index', ['scope' => 'archived']));
    $archivedCodes = batchCodesFromResponse($archivedResponse->json('data') ?? [])->all();

    expect($archivedCodes)->toContain($archivedBatch->batch_code);
    expect($archivedCodes)->not->toContain($activeBatch->batch_code);
});

test('archived batches endpoint returns archived records in html and json', function () {
    $president = presidentUser();
    $activeBatch = makeBatch($president, ['batch_code' => 'B-ACTIVE', 'status' => 'Active', 'stage' => 'Piglet']);
    $archivedBatch = makeBatch($president, ['batch_code' => 'B-ARCH', 'status' => 'Sold', 'stage' => 'For Sale']);

    actingAs($president)
        ->get(route('cycles.archived'))
        ->assertOk()
        ->assertViewIs('cycles.archived');

    $jsonResponse = actingAs($president)->getJson(route('cycles.archived'));

    $jsonResponse->assertOk()->assertJsonStructure([
        'data',
        'meta' => ['current_page', 'last_page', 'per_page', 'total'],
    ]);

    $codes = batchCodesFromResponse($jsonResponse->json('data') ?? [])->all();

    expect($codes)->toContain($archivedBatch->batch_code);
    expect($codes)->not->toContain($activeBatch->batch_code);
});

test('create batch page suggests next batch code from latest soft deleted record', function () {
    $president = presidentUser();

    makeBatch($president, ['batch_code' => 'B-009']);
    $latest = makeBatch($president, ['batch_code' => 'B-010']);
    $latest->delete();

    actingAs($president)
        ->get(route('cycles.create'))
        ->assertOk()
        ->assertViewIs('cycles.create')
        ->assertViewHas('cycleCode', 'C-011');
});

test('president can create batch without auto generated pig profiles', function () {
    $president = presidentUser();

    $response = actingAs($president)->post(route('cycles.store'), batchPayload([
        'batch_code' => 'B-NO-PROFILES',
        'initial_count' => 8,
        'has_pig_profiles' => false,
    ]));

    $batch = PigBatch::query()->where('batch_code', 'B-NO-PROFILES')->firstOrFail();

    $response->assertRedirect(route('cycles.show', $batch));

    assertDatabaseHas('pig_cycles', [
        'id' => $batch->id,
        'initial_count' => 8,
        'current_count' => 8,
        'has_pig_profiles' => false,
    ]);

    assertDatabaseCount('pigs', 0);

    assertDatabaseHas('pig_cycle_status_histories', [
        'batch_id' => $batch->id,
        'old_stage' => null,
        'new_stage' => 'Piglet',
        'old_status' => null,
        'new_status' => 'Active',
    ]);

    assertDatabaseHas('audit_trails', [
        'user_id' => $president->id,
        'action' => 'cycle_created',
        'module' => 'pig_registry',
    ]);
});

test('president can create batch and auto generate pig profiles', function () {
    $president = presidentUser();
    $breeder = makeBreeder($president, [
        'breeder_code' => 'INA-001',
        'name_or_tag' => 'Inahin A',
    ]);

    $response = actingAs($president)->post(route('cycles.store'), batchPayload([
        'batch_code' => 'B-101',
        'breeder_id' => $breeder->id,
        'caretaker_user_id' => $president->id,
        'cycle_number' => 5,
        'initial_count' => 6,
        'has_pig_profiles' => true,
    ]));

    $batch = PigBatch::query()->where('batch_code', 'B-101')->firstOrFail();

    $response->assertRedirect(route('cycles.show', $batch));

    assertDatabaseHas('pig_cycles', [
        'batch_code' => 'B-101',
        'initial_count' => 6,
        'current_count' => 6,
        'has_pig_profiles' => true,
    ]);

    assertDatabaseCount('pigs', 6);

    $pigNos = Pig::query()
        ->where('batch_id', $batch->id)
        ->orderBy('pig_no')
        ->pluck('pig_no')
        ->all();

    expect($pigNos)->toBe([1, 2, 3, 4, 5, 6]);
});

test('batch store validates required fields and enum values', function () {
    $president = presidentUser();

    $response = actingAs($president)->post(route('cycles.store'), batchPayload([
        'batch_code' => '',
        'initial_count' => 0,
        'stage' => 'UnknownStage',
        'status' => 'NotARealStatus',
    ]));

    $response->assertSessionHasErrors(['batch_code', 'initial_count', 'stage', 'status']);
    assertDatabaseCount('pig_cycles', 0);
});

test('batch code must be unique when creating records', function () {
    $president = presidentUser();
    makeBatch($president, ['batch_code' => 'B-UNIQUE-001']);

    $response = actingAs($president)->post(route('cycles.store'), batchPayload([
        'batch_code' => 'B-UNIQUE-001',
    ]));

    $response->assertSessionHasErrors(['batch_code']);
});

test('president can view batch details using batch code route model binding', function () {
    $president = presidentUser();
    $batch = makeBatch($president, ['batch_code' => 'B-SHOW-001']);

    actingAs($president)
        ->get(route('cycles.show', $batch->batch_code))
        ->assertOk()
        ->assertViewIs('cycles.show')
        ->assertViewHas('cycle', fn (PigCycle $viewCycle) => $viewCycle->batch_code === 'B-SHOW-001');
});

test('updating batch details without status change records batch_updated audit', function () {
    $president = presidentUser();
    $batch = makeBatch($president, [
        'stage' => 'Piglet',
        'status' => 'Active',
        'notes' => 'Before update',
    ]);

    $response = actingAs($president)->put(route('cycles.update', $batch), [
        'breeder_id' => null,
        'caretaker_user_id' => $president->id,
        'cycle_number' => 8,
        'average_weight' => 4.40,
        'stage' => 'Piglet',
        'status' => 'Active',
        'notes' => 'Updated profile details',
    ]);

    $response->assertRedirect(route('cycles.show', $batch));

    $batch->refresh();

    expect($batch->cycle_number)->toBe(8);
    expect((string) $batch->notes)->toBe('Updated profile details');

    assertDatabaseHas('audit_trails', [
        'user_id' => $president->id,
        'action' => 'cycle_updated',
        'module' => 'pig_registry',
    ]);

    assertDatabaseMissing('audit_trails', [
        'user_id' => $president->id,
        'action' => 'cycle_status_updated',
        'description' => "Updated stage/status for batch {$batch->batch_code} to {$batch->stage} / {$batch->status}.",
    ]);

    assertDatabaseCount('pig_cycle_status_histories', 0);
});

test('updating batch stage or status creates history and status audit', function () {
    $president = presidentUser();
    $batch = makeBatch($president, [
        'stage' => 'Piglet',
        'status' => 'Active',
    ]);

    $response = actingAs($president)->put(route('cycles.update', $batch), [
        'breeder_id' => null,
        'caretaker_user_id' => $president->id,
        'cycle_number' => 2,
        'average_weight' => 5.25,
        'stage' => 'Fattening',
        'status' => 'Under Monitoring',
        'notes' => 'Moved after weaning phase',
    ]);

    $response->assertRedirect(route('cycles.show', $batch));

    $batch->refresh();
    expect($batch->stage)->toBe('Fattening');
    expect($batch->status)->toBe('Under Monitoring');

    assertDatabaseHas('pig_cycle_status_histories', [
        'batch_id' => $batch->id,
        'old_stage' => 'Piglet',
        'new_stage' => 'Fattening',
        'old_status' => 'Active',
        'new_status' => 'Under Monitoring',
    ]);

    assertDatabaseHas('audit_trails', [
        'user_id' => $president->id,
        'action' => 'cycle_status_updated',
        'module' => 'pig_registry',
    ]);
});

test('archived batch cannot be opened in edit form or updated through regular flow', function () {
    $president = presidentUser();
    $batch = makeBatch($president, [
        'stage' => 'Completed',
        'status' => 'Closed',
    ]);

    actingAs($president)
        ->get(route('cycles.edit', $batch))
        ->assertRedirect(route('cycles.show', $batch))
        ->assertSessionHasErrors(['cycle']);

    actingAs($president)
        ->from(route('cycles.edit', $batch))
        ->put(route('cycles.update', $batch), [
            'stage' => 'Piglet',
            'status' => 'Active',
            'notes' => 'Attempt to update archived batch',
        ])
        ->assertRedirect(route('cycles.edit', $batch))
        ->assertSessionHasErrors(['cycle']);
});

test('regular batch update cannot override counts directly', function () {
    $president = presidentUser();
    $batch = makeBatch($president, [
        'initial_count' => 9,
        'current_count' => 9,
    ]);

    $response = actingAs($president)->put(route('cycles.update', $batch), [
        'stage' => 'Piglet',
        'status' => 'Active',
        'notes' => 'Attempting to bypass adjustment flow.',
        'initial_count' => 1,
        'current_count' => 1,
    ]);

    $response->assertSessionHasErrors(['initial_count', 'current_count']);

    $batch->refresh();

    expect($batch->initial_count)->toBe(9);
    expect($batch->current_count)->toBe(9);
});

test('archiving active batch closes it and records status history and audit', function () {
    $president = presidentUser();
    $batch = makeBatch($president, [
        'stage' => 'For Sale',
        'status' => 'Ready for Sale',
    ]);

    $response = actingAs($president)->patch(route('cycles.archive', $batch), [
        'remarks' => 'Sold and closed this batch.',
    ]);

    $response->assertRedirect(route('cycles.archived'));

    $batch->refresh();

    expect($batch->stage)->toBe('Completed');
    expect($batch->status)->toBe('Closed');

    assertDatabaseHas('pig_cycle_status_histories', [
        'batch_id' => $batch->id,
        'old_stage' => 'For Sale',
        'new_stage' => 'Completed',
        'old_status' => 'Ready for Sale',
        'new_status' => 'Closed',
    ]);

    assertDatabaseHas('audit_trails', [
        'user_id' => $president->id,
        'action' => 'cycle_archived',
        'module' => 'pig_registry',
    ]);
});

test('archiving an already archived batch is idempotent', function () {
    $president = presidentUser();
    $batch = makeBatch($president, [
        'stage' => 'Completed',
        'status' => 'Closed',
    ]);

    actingAs($president)
        ->patch(route('cycles.archive', $batch))
        ->assertRedirect(route('cycles.archived'));

    assertDatabaseCount('pig_cycle_status_histories', 0);
    assertDatabaseMissing('audit_trails', [
        'action' => 'cycle_archived',
        'module' => 'pig_registry',
    ]);
});

test('president can delete archived batch and cascading records are removed', function () {
    $president = presidentUser();
    $batch = makeBatch($president, [
        'stage' => 'Completed',
        'status' => 'Closed',
    ]);

    $pig = Pig::query()->create([
        'batch_id' => $batch->id,
        'pig_no' => 1,
        'status' => 'Active',
        'created_by' => $president->id,
    ]);

    $adjustment = PigBatchAdjustment::query()->create([
        'batch_id' => $batch->id,
        'adjustment_type' => 'decrease',
        'quantity_before' => 10,
        'quantity_change' => -1,
        'quantity_after' => 9,
        'reason' => 'mortality',
        'created_by' => $president->id,
    ]);

    $history = PigBatchStatusHistory::query()->create([
        'batch_id' => $batch->id,
        'old_stage' => 'For Sale',
        'new_stage' => 'Completed',
        'old_status' => 'Ready for Sale',
        'new_status' => 'Closed',
        'changed_by' => $president->id,
    ]);

    actingAs($president)
        ->delete(route('cycles.destroy', $batch))
        ->assertRedirect(route('cycles.archived'));

    assertDatabaseMissing('pig_cycles', ['id' => $batch->id]);
    assertDatabaseMissing('pigs', ['id' => $pig->id]);
    assertDatabaseMissing('pig_cycle_adjustments', ['id' => $adjustment->id]);
    assertDatabaseMissing('pig_cycle_status_histories', ['id' => $history->id]);

    assertDatabaseHas('audit_trails', [
        'user_id' => $president->id,
        'action' => 'cycle_deleted',
        'module' => 'pig_registry',
    ]);
});

test('active batch cannot be deleted', function () {
    $president = presidentUser();
    $batch = makeBatch($president, [
        'stage' => 'Piglet',
        'status' => 'Active',
    ]);

    actingAs($president)
        ->from(route('cycles.show', $batch))
        ->delete(route('cycles.destroy', $batch))
        ->assertRedirect(route('cycles.show', $batch))
        ->assertSessionHasErrors(['cycle']);

    assertDatabaseHas('pig_cycles', ['id' => $batch->id]);
});

test('president can view and search breeder registry using html and json', function () {
    $president = presidentUser();
    $target = makeBreeder($president, [
        'breeder_code' => 'INA-SEARCH-001',
        'name_or_tag' => 'Searchable Breeder',
    ]);
    makeBreeder($president, [
        'breeder_code' => 'INA-SEARCH-002',
        'name_or_tag' => 'Other Breeder',
    ]);

    actingAs($president)
        ->get(route('breeders.create'))
        ->assertOk()
        ->assertViewIs('breeders.create');

    $response = actingAs($president)->getJson(route('breeders.create', [
        'search' => 'SEARCH-001',
    ]));

    $response
        ->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);

    $codes = collect($response->json('data') ?? [])->pluck('breeder_code')->all();

    expect($codes)->toHaveCount(1);
    expect($codes)->toContain($target->breeder_code);
});

test('president can create breeder and audit is recorded', function () {
    $president = presidentUser();

    $response = actingAs($president)->post(route('breeders.store'), [
        'breeder_code' => 'INA-REG-100',
        'name_or_tag' => 'Breeder Nova',
        'reproductive_status' => 'Pregnant',
        'acquisition_date' => '2026-04-01',
        'expected_farrowing_date' => '2026-05-01',
        'notes' => 'Healthy breeder profile.',
    ]);

    $response->assertRedirect(route('breeders.create'));

    assertDatabaseHas('pig_breeders', [
        'breeder_code' => 'INA-REG-100',
        'name_or_tag' => 'Breeder Nova',
        'reproductive_status' => 'Pregnant',
        'created_by' => $president->id,
    ]);

    assertDatabaseHas('audit_trails', [
        'user_id' => $president->id,
        'action' => 'breeder_created',
        'module' => 'pig_registry',
    ]);
});

test('breeder creation validates unique code enum and date constraints', function () {
    $president = presidentUser();
    makeBreeder($president, ['breeder_code' => 'INA-DUPLICATE-01']);

    $response = actingAs($president)->post(route('breeders.store'), [
        'breeder_code' => 'INA-DUPLICATE-01',
        'name_or_tag' => 'Invalid Breeder',
        'reproductive_status' => 'InvalidStatus',
        'acquisition_date' => '2026-06-20',
        'expected_farrowing_date' => '2026-06-01',
    ]);

    $response->assertSessionHasErrors([
        'breeder_code',
        'reproductive_status',
        'expected_farrowing_date',
    ]);
});

test('president can view pig profile manager page', function () {
    $president = presidentUser();
    $batch = makeBatch($president);

    actingAs($president)
        ->get(route('cycles.profiles.index', $batch))
        ->assertOk()
        ->assertViewIs('cycles.pigs')
        ->assertViewHas('cycle', fn (PigCycle $viewCycle) => $viewCycle->id === $batch->id);
});

test('president can add pig profile and profile flag is enabled automatically', function () {
    $president = presidentUser();
    $batch = makeBatch($president, ['has_pig_profiles' => false]);

    $response = actingAs($president)->post(route('cycles.profiles.store', $batch), [
        'pig_no' => 1,
        'ear_mark_type' => 'Left cut',
        'ear_mark_value' => 'L-1',
        'sex' => 'Male',
        'status' => 'Active',
        'remarks' => 'Initial pig profile',
    ]);

    $response->assertRedirect(route('cycles.show', $batch));

    $batch->refresh();
    expect($batch->has_pig_profiles)->toBeTrue();

    assertDatabaseHas('pigs', [
        'batch_id' => $batch->id,
        'pig_no' => 1,
        'status' => 'Active',
        'created_by' => $president->id,
    ]);

    assertDatabaseHas('audit_trails', [
        'user_id' => $president->id,
        'action' => 'pig_profile_created',
        'module' => 'pig_registry',
    ]);
});

test('adding pig profile with out-of-count status automatically decreases batch count', function () {
    $president = presidentUser();
    $batch = makeBatch($president, [
        'current_count' => 10,
        'has_pig_profiles' => false,
    ]);

    actingAs($president)
        ->post(route('cycles.profiles.store', $batch), [
            'pig_no' => 1,
            'status' => 'Deceased',
            'remarks' => 'Died during observation.',
        ])
        ->assertRedirect(route('cycles.show', $batch));

    $batch->refresh();

    expect($batch->current_count)->toBe(9);
    expect($batch->has_pig_profiles)->toBeTrue();

    assertDatabaseHas('pig_cycle_adjustments', [
        'batch_id' => $batch->id,
        'adjustment_type' => 'decrease',
        'quantity_before' => 10,
        'quantity_change' => -1,
        'quantity_after' => 9,
        'reason' => 'mortality',
    ]);

    assertDatabaseHas('audit_trails', [
        'user_id' => $president->id,
        'action' => 'cycle_count_adjusted',
        'module' => 'pig_registry',
    ]);
});

test('adding isolated pig profile also auto decreases active batch count', function () {
    $president = presidentUser();
    $batch = makeBatch($president, [
        'current_count' => 4,
    ]);

    actingAs($president)
        ->post(route('cycles.profiles.store', $batch), [
            'pig_no' => 2,
            'status' => 'Isolated',
            'remarks' => 'Separated for isolation pen.',
        ])
        ->assertRedirect(route('cycles.show', $batch));

    $batch->refresh();

    expect($batch->current_count)->toBe(3);

    assertDatabaseHas('pig_cycle_adjustments', [
        'batch_id' => $batch->id,
        'adjustment_type' => 'decrease',
        'quantity_before' => 4,
        'quantity_change' => -1,
        'quantity_after' => 3,
        'reason' => 'isolated pig',
    ]);
});

test('cannot add pig profile to archived batch', function () {
    $president = presidentUser();
    $batch = makeBatch($president, [
        'stage' => 'Completed',
        'status' => 'Closed',
    ]);

    actingAs($president)
        ->from(route('cycles.profiles.index', $batch))
        ->post(route('cycles.profiles.store', $batch), [
            'pig_no' => 1,
            'status' => 'Active',
        ])
        ->assertRedirect(route('cycles.profiles.index', $batch))
        ->assertSessionHasErrors(['cycle']);

    assertDatabaseCount('pigs', 0);
});

test('deleting counted pig profile automatically decreases batch count', function () {
    $president = presidentUser();
    $batch = makeBatch($president, ['current_count' => 5]);

    $pig = Pig::query()->create([
        'batch_id' => $batch->id,
        'pig_no' => 1,
        'status' => 'Active',
        'created_by' => $president->id,
    ]);

    actingAs($president)
        ->delete(route('cycles.profiles.destroy', [$batch, $pig]))
        ->assertRedirect(route('cycles.profiles.index', $batch));

    $batch->refresh();
    expect($batch->current_count)->toBe(4);

    expect(Pig::query()->find($pig->id))->toBeNull();

    assertDatabaseHas('pig_cycle_adjustments', [
        'batch_id' => $batch->id,
        'adjustment_type' => 'decrease',
        'quantity_before' => 5,
        'quantity_change' => -1,
        'quantity_after' => 4,
        'reason' => 'data correction',
    ]);

    assertDatabaseHas('audit_trails', [
        'user_id' => $president->id,
        'action' => 'cycle_count_adjusted',
        'module' => 'pig_registry',
    ]);

    assertDatabaseHas('audit_trails', [
        'user_id' => $president->id,
        'action' => 'pig_profile_deleted',
        'module' => 'pig_registry',
    ]);
});

test('deleting out-of-count pig profile does not alter current batch count', function () {
    $president = presidentUser();
    $batch = makeBatch($president, ['current_count' => 5]);

    $pig = Pig::query()->create([
        'batch_id' => $batch->id,
        'pig_no' => 2,
        'status' => 'Sold',
        'created_by' => $president->id,
    ]);

    actingAs($president)
        ->delete(route('cycles.profiles.destroy', [$batch, $pig]))
        ->assertRedirect(route('cycles.profiles.index', $batch));

    $batch->refresh();
    expect($batch->current_count)->toBe(5);

    expect(Pig::query()->find($pig->id))->toBeNull();

    assertDatabaseCount('pig_cycle_adjustments', 0);
});

test('cannot delete pig profile when batch is archived', function () {
    $president = presidentUser();
    $batch = makeBatch($president, [
        'stage' => 'Completed',
        'status' => 'Closed',
    ]);

    $pig = Pig::query()->create([
        'batch_id' => $batch->id,
        'pig_no' => 1,
        'status' => 'Active',
        'created_by' => $president->id,
    ]);

    actingAs($president)
        ->from(route('cycles.profiles.index', $batch))
        ->delete(route('cycles.profiles.destroy', [$batch, $pig]))
        ->assertRedirect(route('cycles.profiles.index', $batch))
        ->assertSessionHasErrors(['cycle']);

    expect(Pig::query()->find($pig->id))->not->toBeNull();
});

test('pig number must be unique within a batch but reusable across other batches', function () {
    $president = presidentUser();
    $batchOne = makeBatch($president, ['batch_code' => 'B-PIG-ONE']);
    $batchTwo = makeBatch($president, ['batch_code' => 'B-PIG-TWO']);

    Pig::query()->create([
        'batch_id' => $batchOne->id,
        'pig_no' => 1,
        'status' => 'Active',
        'created_by' => $president->id,
    ]);

    actingAs($president)
        ->post(route('cycles.profiles.store', $batchOne), [
            'pig_no' => 1,
            'status' => 'Active',
        ])
        ->assertSessionHasErrors(['pig_no']);

    actingAs($president)
        ->post(route('cycles.profiles.store', $batchTwo), [
            'pig_no' => 1,
            'status' => 'Active',
        ])
        ->assertRedirect(route('cycles.show', $batchTwo));

    assertDatabaseCount('pigs', 2);
});

test('president can update pig profile details', function () {
    $president = presidentUser();
    $batch = makeBatch($president);
    $pig = Pig::query()->create([
        'batch_id' => $batch->id,
        'pig_no' => 1,
        'status' => 'Active',
        'created_by' => $president->id,
    ]);

    actingAs($president)
        ->put(route('cycles.profiles.update', [$batch, $pig]), [
            'pig_no' => 1,
            'ear_mark_type' => 'Right notch',
            'ear_mark_value' => 'R-9',
            'sex' => 'Female',
            'status' => 'Sick',
            'remarks' => 'Needs treatment',
        ])
        ->assertRedirect(route('cycles.show', $batch));

    assertDatabaseHas('pigs', [
        'id' => $pig->id,
        'pig_no' => 1,
        'ear_mark_type' => 'Right notch',
        'ear_mark_value' => 'R-9',
        'sex' => 'Female',
        'status' => 'Sick',
        'remarks' => 'Needs treatment',
    ]);

    assertDatabaseHas('audit_trails', [
        'user_id' => $president->id,
        'action' => 'pig_profile_updated',
        'module' => 'pig_registry',
    ]);
});

test('updating pig profile status adjusts batch count in both directions', function () {
    $president = presidentUser();
    $batch = makeBatch($president, ['current_count' => 6]);

    $pig = Pig::query()->create([
        'batch_id' => $batch->id,
        'pig_no' => 1,
        'status' => 'Active',
        'created_by' => $president->id,
    ]);

    actingAs($president)
        ->put(route('cycles.profiles.update', [$batch, $pig]), [
            'pig_no' => 1,
            'status' => 'Sold',
            'remarks' => 'Sold in market day.',
        ])
        ->assertRedirect(route('cycles.show', $batch));

    $batch->refresh();
    expect($batch->current_count)->toBe(5);

    assertDatabaseHas('pig_cycle_adjustments', [
        'batch_id' => $batch->id,
        'adjustment_type' => 'decrease',
        'quantity_before' => 6,
        'quantity_change' => -1,
        'quantity_after' => 5,
        'reason' => 'sale deduction',
    ]);

    actingAs($president)
        ->put(route('cycles.profiles.update', [$batch, $pig]), [
            'pig_no' => 1,
            'status' => 'Active',
            'remarks' => 'Status correction after encoding issue.',
        ])
        ->assertRedirect(route('cycles.show', $batch));

    $batch->refresh();
    expect($batch->current_count)->toBe(6);

    assertDatabaseHas('pig_cycle_adjustments', [
        'batch_id' => $batch->id,
        'adjustment_type' => 'increase',
        'quantity_before' => 5,
        'quantity_change' => 1,
        'quantity_after' => 6,
        'reason' => 'transfer',
    ]);
});

test('auto status adjustment rejects transitions that would produce negative batch count', function () {
    $president = presidentUser();
    $batch = makeBatch($president, ['current_count' => 0]);

    actingAs($president)
        ->post(route('cycles.profiles.store', $batch), [
            'pig_no' => 1,
            'status' => 'Deceased',
        ])
        ->assertSessionHasErrors(['status']);

    $batch->refresh();
    expect($batch->current_count)->toBe(0);

    assertDatabaseCount('pig_cycle_adjustments', 0);
});

test('pig update returns 404 when pig is not part of batch route parameter', function () {
    $president = presidentUser();
    $batchA = makeBatch($president, ['batch_code' => 'B-A']);
    $batchB = makeBatch($president, ['batch_code' => 'B-B']);
    $pigInBatchB = Pig::query()->create([
        'batch_id' => $batchB->id,
        'pig_no' => 1,
        'status' => 'Active',
        'created_by' => $president->id,
    ]);

    actingAs($president)
        ->put(route('cycles.profiles.update', [$batchA, $pigInBatchB]), [
            'pig_no' => 1,
            'status' => 'Active',
        ])
        ->assertNotFound();
});

test('cannot update pig profiles in archived batch', function () {
    $president = presidentUser();
    $batch = makeBatch($president, [
        'stage' => 'Completed',
        'status' => 'Closed',
    ]);

    $pig = Pig::query()->create([
        'batch_id' => $batch->id,
        'pig_no' => 1,
        'status' => 'Active',
        'created_by' => $president->id,
    ]);

    actingAs($president)
        ->from(route('cycles.profiles.index', $batch))
        ->put(route('cycles.profiles.update', [$batch, $pig]), [
            'pig_no' => 1,
            'status' => 'Sick',
        ])
        ->assertRedirect(route('cycles.profiles.index', $batch))
        ->assertSessionHasErrors(['cycle']);
});

test('pig profile update enforces per batch uniqueness but allows unchanged own number', function () {
    $president = presidentUser();
    $batch = makeBatch($president);

    $pigOne = Pig::query()->create([
        'batch_id' => $batch->id,
        'pig_no' => 1,
        'status' => 'Active',
        'created_by' => $president->id,
    ]);

    $pigTwo = Pig::query()->create([
        'batch_id' => $batch->id,
        'pig_no' => 2,
        'status' => 'Active',
        'created_by' => $president->id,
    ]);

    actingAs($president)
        ->put(route('cycles.profiles.update', [$batch, $pigOne]), [
            'pig_no' => 1,
            'status' => 'Active',
        ])
        ->assertRedirect(route('cycles.show', $batch));

    actingAs($president)
        ->put(route('cycles.profiles.update', [$batch, $pigTwo]), [
            'pig_no' => 1,
            'status' => 'Active',
        ])
        ->assertSessionHasErrors(['pig_no']);
});

test('increase adjustment normalizes quantity change to positive value', function () {
    $president = presidentUser();
    $batch = makeBatch($president, ['current_count' => 10]);

    actingAs($president)
        ->post(route('cycles.adjustments.store', $batch), [
            'adjustment_type' => 'increase',
            'quantity_change' => -3,
            'reason' => 'recount',
            'remarks' => 'Inventory recount increased count.',
        ])
        ->assertRedirect(route('cycles.show', $batch));

    $batch->refresh();
    expect($batch->current_count)->toBe(13);

    assertDatabaseHas('pig_cycle_adjustments', [
        'batch_id' => $batch->id,
        'adjustment_type' => 'increase',
        'quantity_before' => 10,
        'quantity_change' => 3,
        'quantity_after' => 13,
        'reason' => 'recount',
    ]);
});

test('decrease adjustment updates batch count and records audit trail', function () {
    $president = presidentUser();
    $batch = makeBatch($president, ['current_count' => 10]);

    $adjustmentResponse = actingAs($president)->post(route('cycles.adjustments.store', $batch), [
        'adjustment_type' => 'decrease',
        'quantity_change' => 2,
        'reason' => 'mortality',
        'remarks' => 'Two piglets died during monitoring.',
    ]);

    $adjustmentResponse->assertRedirect(route('cycles.show', $batch));

    $batch->refresh();

    expect($batch->current_count)->toBe(8);

    assertDatabaseHas('pig_cycle_adjustments', [
        'batch_id' => $batch->id,
        'adjustment_type' => 'decrease',
        'quantity_before' => 10,
        'quantity_change' => -2,
        'quantity_after' => 8,
        'reason' => 'mortality',
    ]);

    assertDatabaseHas('audit_trails', [
        'user_id' => $president->id,
        'action' => 'cycle_count_adjusted',
        'module' => 'pig_registry',
    ]);
});

test('correction adjustment can derive delta using resulting count', function () {
    $president = presidentUser();
    $batch = makeBatch($president, ['current_count' => 10]);

    actingAs($president)
        ->post(route('cycles.adjustments.store', $batch), [
            'adjustment_type' => 'correction',
            'quantity_change' => 0,
            'quantity_after' => 7,
            'reason' => 'data correction',
            'remarks' => 'Correcting to verified physical count.',
        ])
        ->assertRedirect(route('cycles.show', $batch));

    $batch->refresh();
    expect($batch->current_count)->toBe(7);

    assertDatabaseHas('pig_cycle_adjustments', [
        'batch_id' => $batch->id,
        'adjustment_type' => 'correction',
        'quantity_before' => 10,
        'quantity_change' => -3,
        'quantity_after' => 7,
        'reason' => 'data correction',
    ]);
});

test('increase and decrease adjustments reject zero quantity change', function () {
    $president = presidentUser();
    $batch = makeBatch($president);

    actingAs($president)
        ->post(route('cycles.adjustments.store', $batch), [
            'adjustment_type' => 'increase',
            'quantity_change' => 0,
            'reason' => 'recount',
        ])
        ->assertSessionHasErrors(['quantity_change']);

    actingAs($president)
        ->post(route('cycles.adjustments.store', $batch), [
            'adjustment_type' => 'decrease',
            'quantity_change' => 0,
            'reason' => 'mortality',
        ])
        ->assertSessionHasErrors(['quantity_change']);
});

test('correction adjustment requires a delta or resulting count', function () {
    $president = presidentUser();
    $batch = makeBatch($president);

    actingAs($president)
        ->post(route('cycles.adjustments.store', $batch), [
            'adjustment_type' => 'correction',
            'quantity_change' => 0,
            'reason' => 'data correction',
        ])
        ->assertSessionHasErrors(['quantity_change']);
});

test('adjustment cannot result in negative current count', function () {
    $president = presidentUser();
    $batch = makeBatch($president, ['current_count' => 2]);

    actingAs($president)
        ->post(route('cycles.adjustments.store', $batch), [
            'adjustment_type' => 'decrease',
            'quantity_change' => 5,
            'reason' => 'mortality',
        ])
        ->assertSessionHasErrors(['quantity_change']);

    $batch->refresh();
    expect($batch->current_count)->toBe(2);
});

test('archived batches cannot be adjusted', function () {
    $president = presidentUser();
    $batch = makeBatch($president, [
        'stage' => 'Completed',
        'status' => 'Closed',
    ]);

    actingAs($president)
        ->from(route('cycles.show', $batch))
        ->post(route('cycles.adjustments.store', $batch), [
            'adjustment_type' => 'decrease',
            'quantity_change' => 1,
            'reason' => 'mortality',
        ])
        ->assertRedirect(route('cycles.show', $batch))
        ->assertSessionHasErrors(['cycle']);
});

test('status update can change stage while keeping current status', function () {
    $president = presidentUser();
    $batch = makeBatch($president, [
        'stage' => 'Piglet',
        'status' => 'Active',
    ]);

    actingAs($president)
        ->post(route('cycles.status.store', $batch), [
            'new_stage' => 'Weaning',
            'remarks' => 'Reached weaning phase.',
        ])
        ->assertRedirect(route('cycles.show', $batch));

    $batch->refresh();
    expect($batch->stage)->toBe('Weaning');
    expect($batch->status)->toBe('Active');

    assertDatabaseHas('pig_cycle_status_histories', [
        'batch_id' => $batch->id,
        'old_stage' => 'Piglet',
        'new_stage' => 'Weaning',
        'old_status' => 'Active',
        'new_status' => 'Active',
    ]);

    assertDatabaseHas('audit_trails', [
        'user_id' => $president->id,
        'action' => 'cycle_status_updated',
        'module' => 'pig_registry',
    ]);
});

test('status update can change status while keeping current stage', function () {
    $president = presidentUser();
    $batch = makeBatch($president, [
        'stage' => 'Fattening',
        'status' => 'Under Monitoring',
    ]);

    actingAs($president)
        ->post(route('cycles.status.store', $batch), [
            'new_status' => 'Ready for Sale',
            'remarks' => 'Weight target reached.',
        ])
        ->assertRedirect(route('cycles.show', $batch));

    $batch->refresh();
    expect($batch->stage)->toBe('Fattening');
    expect($batch->status)->toBe('Ready for Sale');
});

test('status update can reopen archived batch', function () {
    $president = presidentUser();
    $batch = makeBatch($president, [
        'stage' => 'Completed',
        'status' => 'Closed',
    ]);

    actingAs($president)
        ->post(route('cycles.status.store', $batch), [
            'new_stage' => 'Piglet',
            'new_status' => 'Active',
            'remarks' => 'Reopened after reassessment.',
        ])
        ->assertRedirect(route('cycles.show', $batch));

    $batch->refresh();
    expect($batch->stage)->toBe('Piglet');
    expect($batch->status)->toBe('Active');
});

test('status update requires at least one new value', function () {
    $president = presidentUser();
    $batch = makeBatch($president);

    actingAs($president)
        ->post(route('cycles.status.store', $batch), [
            'remarks' => 'No fields changed.',
        ])
        ->assertSessionHasErrors(['new_status']);
});

test('status update rejects unchanged stage and status values', function () {
    $president = presidentUser();
    $batch = makeBatch($president, [
        'stage' => 'Piglet',
        'status' => 'Active',
    ]);

    actingAs($president)
        ->post(route('cycles.status.store', $batch), [
            'new_stage' => 'Piglet',
            'new_status' => 'Active',
            'remarks' => 'No effective update',
        ])
        ->assertSessionHasErrors(['new_status']);
});

test('batch lifecycle operations produce expected audit rows only', function () {
    $president = presidentUser();
    $batch = makeBatch($president);

    actingAs($president)->post(route('cycles.adjustments.store', $batch), [
        'adjustment_type' => 'increase',
        'quantity_change' => 2,
        'reason' => 'recount',
    ])->assertRedirect(route('cycles.show', $batch));

    actingAs($president)->post(route('cycles.status.store', $batch), [
        'new_stage' => 'Weaning',
    ])->assertRedirect(route('cycles.show', $batch));

    $actions = AuditTrail::query()
        ->where('module', 'pig_registry')
        ->pluck('action')
        ->all();

    expect($actions)->toContain('cycle_count_adjusted');
    expect($actions)->toContain('cycle_status_updated');
});

