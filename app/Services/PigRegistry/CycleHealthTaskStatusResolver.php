<?php

namespace App\Services\PigRegistry;

use App\Models\CycleHealthTask;
use Illuminate\Support\Carbon;

class CycleHealthTaskStatusResolver
{
    public function refreshTask(CycleHealthTask $task): CycleHealthTask
    {
        $targetCount = max(0, (int) $task->target_count);
        $completedCount = max(0, min((int) $task->completed_count, $targetCount));
        $remainingCount = max(0, $targetCount - $completedCount);
        $resolvedStatus = $this->resolveStatus(
            (string) $task->status,
            $targetCount,
            $completedCount,
            $task->planned_start_date,
            $task->actual_date,
        );

        $dirty = false;

        if ((int) $task->completed_count !== $completedCount) {
            $task->completed_count = $completedCount;
            $dirty = true;
        }

        if ((int) $task->remaining_count !== $remainingCount) {
            $task->remaining_count = $remainingCount;
            $dirty = true;
        }

        if ((string) $task->status !== $resolvedStatus) {
            $task->status = $resolvedStatus;
            $dirty = true;
        }

        if ($resolvedStatus === 'completed' && $task->actual_date === null) {
            $task->forceFill([
                'actual_date' => now()->toDateString(),
            ]);
            $dirty = true;
        }

        if ($dirty) {
            $task->save();
        }

        return $task;
    }

    public function resolveStatus(
        string $currentStatus,
        int $targetCount,
        int $completedCount,
        Carbon|string|null $plannedStartDate,
        Carbon|string|null $actualDate = null
    ): string {
        if ($targetCount > 0 && $completedCount >= $targetCount) {
            return 'completed';
        }

        if ($completedCount > 0 && $completedCount < $targetCount) {
            return 'partially_completed';
        }

        if (in_array($currentStatus, ['skipped', 'rescheduled', 'not_applicable'], true)) {
            return $currentStatus;
        }

        if ($actualDate !== null && $completedCount === 0) {
            return 'in_progress';
        }

        $plannedDate = $plannedStartDate instanceof Carbon
            ? $plannedStartDate->copy()->startOfDay()
            : ($plannedStartDate !== null ? Carbon::parse((string) $plannedStartDate)->startOfDay() : null);

        if ($plannedDate !== null && $plannedDate->lt(today())) {
            return 'overdue';
        }

        return 'pending';
    }
}
