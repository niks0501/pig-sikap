<?php

namespace App\Services\PigRegistry;

use App\Models\CycleHealthTask;
use App\Models\PigCycle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CycleHealthSummaryService
{
    public function __construct(
        private readonly CycleHealthTaskStatusResolver $statusResolver
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

        $dueToday = $tasks->filter(function (CycleHealthTask $task): bool {
            $plannedStartDate = $this->toCarbon($task->planned_start_date);

            return $plannedStartDate?->isToday() === true
                && ! in_array($task->status, CycleHealthTask::TERMINAL_STATUSES, true);
        });

        $overdue = $tasks->filter(function (CycleHealthTask $task): bool {
            if ($task->status === 'overdue') {
                return true;
            }

            $plannedStartDate = $this->toCarbon($task->planned_start_date);

            return $plannedStartDate?->lt(today()) === true
                && ! in_array($task->status, CycleHealthTask::TERMINAL_STATUSES, true);
        });

        $upcoming = $tasks->filter(function (CycleHealthTask $task): bool {
            $plannedStartDate = $this->toCarbon($task->planned_start_date);

            return $plannedStartDate?->gt(today()) === true;
        });

        $completedRecently = $tasks->filter(function (CycleHealthTask $task): bool {
            if ($task->status !== 'completed' || $task->actual_date === null) {
                return false;
            }

            $actualDate = $this->toCarbon($task->actual_date);

            return $actualDate?->gte(today()->subDays(14)) === true;
        });

        $nextDueTask = $tasks
            ->filter(fn (CycleHealthTask $task): bool => in_array($task->status, ['pending', 'in_progress', 'overdue', 'rescheduled', 'partially_completed'], true))
            ->sortBy('planned_start_date')
            ->first();

        $activeOralMedication = $tasks
            ->filter(fn (CycleHealthTask $task): bool => $task->task_type === 'oral_medication_period')
            ->first(function (CycleHealthTask $task): bool {
                $start = $this->toCarbon($task->planned_start_date);
                $end = $this->toCarbon($task->planned_end_date);

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

        $mortalityCount = (int) $incidents
            ->where('incident_type', 'deceased')
            ->sum('affected_count');

        return [
            'counts' => [
                'due_today' => $dueToday->count(),
                'overdue' => $overdue->count(),
                'upcoming' => $upcoming->count(),
                'completed_recently' => $completedRecently->count(),
                'incidents' => $incidents->count(),
                'mortality' => $mortalityCount,
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

        return $this->toDateString($task?->actual_date);
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
            'planned_start_date' => $this->toDateString($task->planned_start_date),
            'planned_end_date' => $this->toDateString($task->planned_end_date),
            'actual_date' => $this->toDateString($task->actual_date),
            'target_count' => (int) $task->target_count,
            'completed_count' => (int) $task->completed_count,
            'remaining_count' => (int) $task->remaining_count,
            'is_optional' => (bool) $task->is_optional,
        ];
    }

    private function toCarbon(mixed $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->copy()->startOfDay();
        }

        return Carbon::parse((string) $value)->startOfDay();
    }

    private function toDateString(mixed $value): ?string
    {
        return $this->toCarbon($value)?->toDateString();
    }
}
