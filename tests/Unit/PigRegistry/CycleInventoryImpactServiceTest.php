<?php

use App\Models\PigCycle;
use App\Models\User;
use App\Services\PigRegistry\CycleInventoryImpactService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeInventoryUnitCycle(array $overrides = []): PigCycle
{
    $actor = User::factory()->create();

    return PigCycle::query()->create([
        'batch_code' => 'CIU-'.fake()->unique()->numerify('###'),
        'caretaker_user_id' => $actor->id,
        'cycle_number' => 1,
        'date_of_purchase' => now()->subDays(10)->toDateString(),
        'initial_count' => 10,
        'current_count' => 10,
        'average_weight' => 7.10,
        'stage' => 'Growing',
        'status' => 'Active',
        'has_pig_profiles' => false,
        'notes' => 'Cycle inventory impact unit test',
        'last_reviewed_at' => now(),
        'created_by' => $actor->id,
        ...$overrides,
    ]);
}

test('apply is idempotent for the same source event key on the same cycle', function () {
    $cycle = makeInventoryUnitCycle();
    $actor = User::query()->findOrFail($cycle->created_by);
    $service = app(CycleInventoryImpactService::class);

    $first = $service->apply($cycle, [
        'adjustment_type' => 'decrease',
        'quantity_change' => 2,
        'reason' => 'mortality',
        'source_module' => 'health_monitoring',
        'source_type' => 'cycle_health_incident',
        'source_id' => 101,
        'source_event_key' => 'evt-health-101',
    ], $actor);

    $second = $service->apply($cycle, [
        'adjustment_type' => 'decrease',
        'quantity_change' => 5,
        'reason' => 'mortality',
        'source_module' => 'health_monitoring',
        'source_type' => 'cycle_health_incident',
        'source_id' => 101,
        'source_event_key' => 'evt-health-101',
    ], $actor);

    $cycle->refresh();

    expect($first->id)->toBe($second->id);
    expect($cycle->current_count)->toBe(8);
    expect($cycle->adjustments()->where('source_event_key', 'evt-health-101')->count())->toBe(1);
});

test('apply rejects source event key reuse across different cycles', function () {
    $firstCycle = makeInventoryUnitCycle(['batch_code' => 'CIU-FIRST-001']);
    $secondCycle = makeInventoryUnitCycle(['batch_code' => 'CIU-SECOND-002']);
    $actor = User::query()->findOrFail($firstCycle->created_by);
    $service = app(CycleInventoryImpactService::class);

    $service->apply($firstCycle, [
        'adjustment_type' => 'decrease',
        'quantity_change' => 1,
        'reason' => 'mortality',
        'source_event_key' => 'evt-shared-key',
    ], $actor);

    expect(function () use ($service, $secondCycle, $actor): void {
        $service->apply($secondCycle, [
            'adjustment_type' => 'decrease',
            'quantity_change' => 1,
            'reason' => 'mortality',
            'source_event_key' => 'evt-shared-key',
        ], $actor);
    })->toThrow(ValidationException::class);
});

test('correction adjustments can derive delta from quantity_after', function () {
    $cycle = makeInventoryUnitCycle(['current_count' => 9]);
    $actor = User::query()->findOrFail($cycle->created_by);
    $service = app(CycleInventoryImpactService::class);

    $adjustment = $service->apply($cycle, [
        'adjustment_type' => 'correction',
        'quantity_after' => 7,
        'reason' => 'recount',
        'source_event_key' => 'evt-correction-777',
    ], $actor);

    $cycle->refresh();

    expect($cycle->current_count)->toBe(7);
    expect((int) $adjustment->quantity_before)->toBe(9);
    expect((int) $adjustment->quantity_change)->toBe(-2);
    expect((int) $adjustment->quantity_after)->toBe(7);
});
