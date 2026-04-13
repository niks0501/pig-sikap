<?php

use App\Models\CycleHealthTask;
use App\Models\PigCycle;
use App\Models\User;
use App\Services\PigRegistry\UpdateCycleHealthTaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeCycleForHealthActionTests(array $overrides = []): PigCycle
{
    $user = User::factory()->create();

    return PigCycle::query()->create([
        'batch_code' => 'HA-'.fake()->unique()->numerify('###'),
        'caretaker_user_id' => $user->id,
        'cycle_number' => 1,
        'date_of_purchase' => now()->subDays(10)->toDateString(),
        'initial_count' => 10,
        'current_count' => 10,
        'average_weight' => 8.40,
        'stage' => 'Growing',
        'status' => 'Active',
        'has_pig_profiles' => false,
        'notes' => 'Health task action test cycle',
        'last_reviewed_at' => now(),
        'created_by' => $user->id,
        ...$overrides,
    ]);
}

function makeTaskForActionTests(PigCycle $cycle, array $overrides = []): CycleHealthTask
{
    return CycleHealthTask::query()->create([
        'batch_id' => $cycle->id,
        'task_name' => 'Injectable Vitamins',
        'task_type' => 'injectable',
        'planned_start_date' => now()->toDateString(),
        'planned_end_date' => null,
        'actual_date' => null,
        'status' => 'pending',
        'target_count' => 10,
        'completed_count' => 0,
        'remaining_count' => 10,
        'is_optional' => false,
        'remarks' => null,
        'follow_up_date' => null,
        'completed_by' => null,
        ...$overrides,
    ]);
}

test('partial action computes partially completed status and remaining count', function () {
    $cycle = makeCycleForHealthActionTests();
    $actor = User::query()->findOrFail($cycle->created_by);
    $task = makeTaskForActionTests($cycle);

    app(UpdateCycleHealthTaskService::class)->handle($task, [
        'action' => 'partial',
        'completed_count' => 4,
        'actual_date' => now()->toDateString(),
        'follow_up_date' => now()->addDay()->toDateString(),
        'remarks' => 'Partial update unit test',
    ], $actor);

    $task->refresh();

    expect($task->status)->toBe('partially_completed');
    expect($task->remaining_count)->toBe(6);
    expect($task->completed_count)->toBe(4);
});

test('reschedule action keeps task in rescheduled status', function () {
    $cycle = makeCycleForHealthActionTests();
    $actor = User::query()->findOrFail($cycle->created_by);
    $task = makeTaskForActionTests($cycle, [
        'planned_start_date' => now()->subDay()->toDateString(),
    ]);

    app(UpdateCycleHealthTaskService::class)->handle($task, [
        'action' => 'reschedule',
        'planned_start_date' => now()->addDays(3)->toDateString(),
        'follow_up_date' => now()->addDays(5)->toDateString(),
        'remarks' => 'Rescheduled due to rain',
    ], $actor);

    $task->refresh();

    expect($task->status)->toBe('rescheduled');
    expect((string) $task->planned_start_date)->toContain(now()->addDays(3)->toDateString());
});

test('optional task can be skipped', function () {
    $cycle = makeCycleForHealthActionTests();
    $actor = User::query()->findOrFail($cycle->created_by);
    $task = makeTaskForActionTests($cycle, [
        'is_optional' => true,
        'task_type' => 'maintenance_optional',
        'task_name' => 'Optional Monthly Maintenance',
    ]);

    app(UpdateCycleHealthTaskService::class)->handle($task, [
        'action' => 'skip',
        'remarks' => 'Not required this month',
    ], $actor);

    $task->refresh();

    expect($task->status)->toBe('skipped');
});

test('cycle health task status labels use corporate style capitalization', function () {
    expect(CycleHealthTask::formatStatusLabel('pending'))->toBe('Pending');
    expect(CycleHealthTask::formatStatusLabel('in_progress'))->toBe('In Progress');
    expect(CycleHealthTask::formatStatusLabel('completed'))->toBe('Completed');
    expect(CycleHealthTask::formatStatusLabel('partially_completed'))->toBe('Partially Completed');
    expect(CycleHealthTask::formatStatusLabel('skipped'))->toBe('Skipped');
    expect(CycleHealthTask::formatStatusLabel('rescheduled'))->toBe('Rescheduled');
    expect(CycleHealthTask::formatStatusLabel('overdue'))->toBe('Overdue');
    expect(CycleHealthTask::formatStatusLabel('not_applicable'))->toBe('Not Applicable');
});
