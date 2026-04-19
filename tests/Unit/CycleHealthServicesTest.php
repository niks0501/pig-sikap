<?php

use App\Models\PigCycle;
use App\Models\PigCycleAdjustment;
use App\Models\CycleHealthTask;
use App\Models\User;
use App\Services\PigRegistry\CycleHealthPlanGenerator;
use App\Services\PigRegistry\CycleHealthSummaryService;
use App\Services\PigRegistry\RecordHealthIncidentWithOperationalImpactService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeCycleForHealthTests(array $overrides = []): PigCycle
{
    $user = User::factory()->create();

    return PigCycle::query()->create([
        'batch_code' => 'H-'.fake()->unique()->numerify('###'),
        'caretaker_user_id' => $user->id,
        'cycle_number' => 1,
        'date_of_purchase' => now()->subDays(20)->toDateString(),
        'initial_count' => 10,
        'current_count' => 10,
        'average_weight' => 9.25,
        'stage' => 'Growing',
        'status' => 'Active',
        'has_pig_profiles' => false,
        'notes' => 'Cycle health test record',
        'last_reviewed_at' => now(),
        'created_by' => $user->id,
        ...$overrides,
    ]);
}

test('cycle health plan generator creates default post purchase tasks', function () {
    $cycle = makeCycleForHealthTests([
        'date_of_purchase' => now()->subDays(5)->toDateString(),
    ]);

    app(CycleHealthPlanGenerator::class)->assignDefaultTemplateAndGenerateTasks($cycle);

    $cycle->refresh();

    expect($cycle->health_template_id)->not->toBeNull();

    $tasks = $cycle->healthTasks()->orderBy('planned_start_date')->orderBy('id')->get();

    expect($tasks)->toHaveCount(4);

    $oralTask = $tasks->firstWhere('task_type', 'oral_medication_period');

    expect($oralTask)->not->toBeNull();
    expect((string) $oralTask->planned_start_date)->toContain(now()->subDays(5)->toDateString());
    expect((string) $oralTask->planned_end_date)->toContain(now()->addDays(40)->toDateString());
    expect((bool) $oralTask->is_optional)->toBeFalse();

    $maintenanceTask = $tasks->firstWhere('task_type', 'maintenance_optional');

    expect((bool) $maintenanceTask?->is_optional)->toBeTrue();
});

test('cycle health summary service returns due counts and mortality totals', function () {
    $cycle = makeCycleForHealthTests([
        'date_of_purchase' => now()->subDays(60)->toDateString(),
    ]);

    app(CycleHealthPlanGenerator::class)->assignDefaultTemplateAndGenerateTasks($cycle);

    /** @var CycleHealthTask $task */
    $task = $cycle->healthTasks()->where('task_type', 'injectable')->firstOrFail();

    $task->update([
        'completed_count' => (int) $task->target_count,
        'actual_date' => now()->subDay()->toDateString(),
        'status' => 'completed',
    ]);

    $cycle->healthIncidents()->create([
        'incident_type' => 'deceased',
        'date_reported' => now()->toDateString(),
        'affected_count' => 2,
        'remarks' => 'Mortality test',
        'reported_by' => $cycle->created_by,
    ]);

    $summary = app(CycleHealthSummaryService::class)->handle($cycle);

    expect($summary['counts']['incidents'])->toBe(1);
    expect($summary['counts']['mortality'])->toBe(2);
    expect($summary['counts']['currently_affected'])->toBe(0);
    expect($summary['counts']['total_deceased_reported'])->toBe(2);
    expect($summary['last_injectable_date'])->toBe(now()->subDay()->toDateString());
    expect($summary['next_due_task'])->not->toBeNull();
});

test('recording deceased incident via orchestrator auto deducts cycle current count', function () {
    $cycle = makeCycleForHealthTests([
        'current_count' => 12,
        'initial_count' => 12,
    ]);

    $actor = User::query()->findOrFail($cycle->created_by);

    $incident = app(RecordHealthIncidentWithOperationalImpactService::class)->handle($cycle, [
        'event_key' => fake()->uuid(),
        'incident_type' => 'deceased',
        'date_reported' => now()->toDateString(),
        'affected_count' => 3,
        'remarks' => 'Auto adjustment test',
    ], $actor);

    $cycle->refresh();

    expect($incident->incident_type)->toBe('deceased');
    expect($cycle->current_count)->toBe(9);

    $adjustment = PigCycleAdjustment::query()
        ->where('batch_id', $cycle->id)
        ->latest('id')
        ->first();

    expect($adjustment)->not->toBeNull();
    expect((string) $adjustment?->reason)->toBe('mortality');
    expect((int) $adjustment?->quantity_after)->toBe(9);
});
