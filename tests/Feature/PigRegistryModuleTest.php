<?php

use App\Models\PigBatch;
use App\Models\PigBreeder;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function () {
    seed(RoleSeeder::class);
});

test('president can access pig registry index', function () {
    $presidentRole = Role::where('slug', 'president')->firstOrFail();

    $president = User::factory()->create([
        'role_id' => $presidentRole->id,
        'is_active' => true,
        'must_change_password' => false,
        'email_verified_at' => now(),
    ]);

    $response = actingAs($president)->get(route('batches.index'));

    $response->assertOk();
});

test('non president is forbidden from pig registry index', function () {
    $secretaryRole = Role::where('slug', 'secretary')->firstOrFail();

    $secretary = User::factory()->create([
        'role_id' => $secretaryRole->id,
        'is_active' => true,
        'must_change_password' => false,
        'email_verified_at' => now(),
    ]);

    $response = actingAs($secretary)->get(route('batches.index'));

    $response->assertForbidden();
});

test('president can create batch and auto generate pig profiles', function () {
    $presidentRole = Role::where('slug', 'president')->firstOrFail();

    $president = User::factory()->create([
        'role_id' => $presidentRole->id,
        'is_active' => true,
        'must_change_password' => false,
        'email_verified_at' => now(),
    ]);

    $breeder = PigBreeder::query()->create([
        'breeder_code' => 'INA-001',
        'name_or_tag' => 'Inahin A',
        'reproductive_status' => 'Active',
        'created_by' => $president->id,
    ]);

    $response = actingAs($president)->post(route('batches.store'), [
        'batch_code' => 'B-101',
        'breeder_id' => $breeder->id,
        'caretaker_user_id' => $president->id,
        'cycle_number' => 5,
        'birth_date' => now()->toDateString(),
        'initial_count' => 6,
        'average_weight' => 3.25,
        'stage' => 'Piglet',
        'status' => 'Active',
        'has_pig_profiles' => true,
        'notes' => 'Initial registration test.',
    ]);

    $batch = PigBatch::query()->where('batch_code', 'B-101')->firstOrFail();

    $response->assertRedirect(route('batches.show', $batch));

    assertDatabaseHas('pig_batches', [
        'batch_code' => 'B-101',
        'initial_count' => 6,
        'current_count' => 6,
        'has_pig_profiles' => true,
    ]);

    assertDatabaseCount('pigs', 6);

    assertDatabaseHas('pig_batch_status_histories', [
        'batch_id' => $batch->id,
        'new_stage' => 'Piglet',
        'new_status' => 'Active',
    ]);

    assertDatabaseHas('audit_trails', [
        'user_id' => $president->id,
        'action' => 'batch_created',
        'module' => 'pig_registry',
    ]);
});

test('count adjustment and status update are tracked', function () {
    $presidentRole = Role::where('slug', 'president')->firstOrFail();

    $president = User::factory()->create([
        'role_id' => $presidentRole->id,
        'is_active' => true,
        'must_change_password' => false,
        'email_verified_at' => now(),
    ]);

    $batch = PigBatch::query()->create([
        'batch_code' => 'B-202',
        'birth_date' => now()->toDateString(),
        'initial_count' => 10,
        'current_count' => 10,
        'stage' => 'Piglet',
        'status' => 'Active',
        'has_pig_profiles' => false,
        'created_by' => $president->id,
    ]);

    $adjustmentResponse = actingAs($president)->post(route('batches.adjustments.store', $batch), [
        'adjustment_type' => 'decrease',
        'quantity_change' => 2,
        'reason' => 'mortality',
        'remarks' => 'Two piglets died during monitoring.',
    ]);

    $adjustmentResponse->assertRedirect(route('batches.show', $batch));

    $batch->refresh();

    expect($batch->current_count)->toBe(8);

    assertDatabaseHas('pig_batch_adjustments', [
        'batch_id' => $batch->id,
        'adjustment_type' => 'decrease',
        'quantity_before' => 10,
        'quantity_after' => 8,
        'reason' => 'mortality',
    ]);

    $statusResponse = actingAs($president)->post(route('batches.status.store', $batch), [
        'new_stage' => 'Fattening',
        'new_status' => 'Under Monitoring',
        'remarks' => 'Moved after weaning period.',
    ]);

    $statusResponse->assertRedirect(route('batches.show', $batch));

    $batch->refresh();

    expect($batch->stage)->toBe('Fattening');
    expect($batch->status)->toBe('Under Monitoring');

    assertDatabaseHas('pig_batch_status_histories', [
        'batch_id' => $batch->id,
        'new_stage' => 'Fattening',
        'new_status' => 'Under Monitoring',
    ]);

    assertDatabaseHas('audit_trails', [
        'user_id' => $president->id,
        'action' => 'batch_count_adjusted',
        'module' => 'pig_registry',
    ]);

    assertDatabaseHas('audit_trails', [
        'user_id' => $president->id,
        'action' => 'batch_status_updated',
        'module' => 'pig_registry',
    ]);
});

test('regular batch update cannot override counts directly', function () {
    $presidentRole = Role::where('slug', 'president')->firstOrFail();

    $president = User::factory()->create([
        'role_id' => $presidentRole->id,
        'is_active' => true,
        'must_change_password' => false,
        'email_verified_at' => now(),
    ]);

    $batch = PigBatch::query()->create([
        'batch_code' => 'B-303',
        'birth_date' => now()->toDateString(),
        'initial_count' => 9,
        'current_count' => 9,
        'stage' => 'Piglet',
        'status' => 'Active',
        'has_pig_profiles' => false,
        'created_by' => $president->id,
    ]);

    $response = actingAs($president)->put(route('batches.update', $batch), [
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
