<?php

namespace App\Services\PigRegistry;

use App\Models\CycleHealthTask;
use App\Models\PigCycle;
use Illuminate\Support\Collection;

class CycleHealthSummaryService
{
    private const RECENT_COMPLETION_WINDOW_DAYS = 14;

    public function __construct(
        private readonly CycleHealthTaskStatusResolver $statusResolver,
        private readonly CycleHealthDateNormalizer $dateNormalizer,
        private readonly CycleHealthStateProjector $cycleHealthStateProjector
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(PigCycle $cycle): array
    {
        $tasks = $cycle->healthTasks()
            ->orderBy('planned_start_date')
            ->orderBy('id')
            ->get();

        $tasks->each(function (CycleHealthTask $task): void {
            $this->statusResolver->refreshTask($task);
        });

        $incidents = $cycle->healthIncidents()->get();
        $projectedHealth = $this->cycleHealthStateProjector->projectIncidents($incidents, (int) $cycle->current_count);
        $activeMetrics = $projectedHealth['active'] ?? [];
        $lifetimeMetrics = $projectedHealth['lifetime'] ?? [];

        $dueToday = $tasks->filter(function (CycleHealthTask $task): bool {
            $plannedStartDate = $this->dateNormalizer->toCarbon($task->planned_start_date);

            return $plannedStartDate?->isToday() === true
                && ! in_array($task->status, CycleHealthTask::TERMINAL_STATUSES, true);
        });

        $overdue = $tasks->filter(function (CycleHealthTask $task): bool {
            if ($task->status === 'overdue') {
                return true;
            }

            $plannedStartDate = $this->dateNormalizer->toCarbon($task->planned_start_date);

            return $plannedStartDate?->lt(today()) === true
                && ! in_array($task->status, CycleHealthTask::TERMINAL_STATUSES, true);
        });

        $upcoming = $tasks->filter(function (CycleHealthTask $task): bool {
            $plannedStartDate = $this->dateNormalizer->toCarbon($task->planned_start_date);

            return $plannedStartDate?->gt(today()) === true;
        });

        $completedRecently = $tasks->filter(function (CycleHealthTask $task): bool {
            if ($task->status !== 'completed' || $task->actual_date === null) {
                return false;
            }

            $actualDate = $this->dateNormalizer->toCarbon($task->actual_date);

            return $actualDate?->gte(today()->subDays(self::RECENT_COMPLETION_WINDOW_DAYS)) === true;
        });

        $nextDueTask = $tasks
            ->filter(fn (CycleHealthTask $task): bool => in_array($task->status, ['pending', 'in_progress', 'overdue', 'rescheduled', 'partially_completed'], true))
            ->sortBy('planned_start_date')
            ->first();

        $activeOralMedication = $tasks
            ->filter(fn (CycleHealthTask $task): bool => $task->task_type === 'oral_medication_period')
            ->first(function (CycleHealthTask $task): bool {
                $start = $this->dateNormalizer->toCarbon($task->planned_start_date);
                $end = $this->dateNormalizer->toCarbon($task->planned_end_date);

                if ($start === null) {
                    return false;
                }

                if ($end === null) {
                    return $start->lte(today());
                }

                return $start->lte(today()) && $end->gte(today());
            });

        $lastInjectable = $this->lastCompletedDateByTaskType($tasks, 'injectable');
        $lastDeworming = $this->lastCompletedDateByTaskType($tasks, 'deworming');

        $mortalityCount = (int) ($lifetimeMetrics['total_deceased_reported'] ?? 0);

        return [
            'counts' => [
                'due_today' => $dueToday->count(),
                'overdue' => $overdue->count(),
                'upcoming' => $upcoming->count(),
                'completed_recently' => $completedRecently->count(),
                'incidents' => $incidents->count(),
                'mortality' => $mortalityCount,
                'currently_sick' => (int) ($activeMetrics['currently_sick'] ?? 0),
                'currently_isolated' => (int) ($activeMetrics['currently_isolated'] ?? 0),
                'currently_affected' => (int) ($activeMetrics['currently_affected'] ?? 0),
                'healthy_now' => (int) ($activeMetrics['healthy_now'] ?? 0),
                'total_sick_reported' => (int) ($lifetimeMetrics['total_sick_reported'] ?? 0),
                'total_isolated_reported' => (int) ($lifetimeMetrics['total_isolated_reported'] ?? 0),
                'total_recovered_reported' => (int) ($lifetimeMetrics['total_recovered_reported'] ?? 0),
                'total_deceased_reported' => (int) ($lifetimeMetrics['total_deceased_reported'] ?? 0),
            ],
            'active' => [
                'currently_sick' => (int) ($activeMetrics['currently_sick'] ?? 0),
                'currently_isolated' => (int) ($activeMetrics['currently_isolated'] ?? 0),
                'currently_affected' => (int) ($activeMetrics['currently_affected'] ?? 0),
                'healthy_now' => (int) ($activeMetrics['healthy_now'] ?? 0),
            ],
            'lifetime' => [
                'total_sick_reported' => (int) ($lifetimeMetrics['total_sick_reported'] ?? 0),
                'total_isolated_reported' => (int) ($lifetimeMetrics['total_isolated_reported'] ?? 0),
                'total_recovered_reported' => (int) ($lifetimeMetrics['total_recovered_reported'] ?? 0),
                'total_deceased_reported' => (int) ($lifetimeMetrics['total_deceased_reported'] ?? 0),
            ],
            'next_due_task' => $this->mapTask($nextDueTask),
            'active_oral_medication' => $this->mapTask($activeOralMedication),
            'last_injectable_date' => $lastInjectable,
            'last_deworming_date' => $lastDeworming,
        ];
    }

    private function lastCompletedDateByTaskType(Collection $tasks, string $taskType): ?string
    {
        /** @var CycleHealthTask|null $task */
        $task = $tasks
            ->filter(fn (CycleHealthTask $item): bool => $item->task_type === $taskType && $item->actual_date !== null)
            ->sortByDesc('actual_date')
            ->first();

        return $this->dateNormalizer->toDateString($task?->actual_date);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function mapTask(?CycleHealthTask $task): ?array
    {
        if ($task === null) {
            return null;
        }

        return [
            'id' => $task->id,
            'task_name' => $task->task_name,
            'task_type' => $task->task_type,
            'status' => $task->status,
            'planned_start_date' => $this->dateNormalizer->toDateString($task->planned_start_date),
            'planned_end_date' => $this->dateNormalizer->toDateString($task->planned_end_date),
            'actual_date' => $this->dateNormalizer->toDateString($task->actual_date),
            'target_count' => (int) $task->target_count,
            'completed_count' => (int) $task->completed_count,
            'remaining_count' => (int) $task->remaining_count,
            'is_optional' => (bool) $task->is_optional,
        ];
    }

}
