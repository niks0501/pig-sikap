<?php

namespace App\Services\PigRegistry;

use App\Models\CycleHealthTask;
use Illuminate\Support\Carbon;

class CycleHealthExpenseDraftBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function fromTask(CycleHealthTask $task): array
    {
        return [
            'batch_id' => $task->batch_id,
            'category' => $this->mapExpenseCategory($task->task_type),
            'expense_date' => $this->toDateString($task->actual_date) ?? $this->toDateString($task->planned_start_date),
            'notes' => trim(implode(' | ', array_filter([
                'Draft from health task',
                $task->task_name,
                $task->remarks,
            ]))),
            'source' => [
                'type' => 'cycle_health_task',
                'id' => $task->id,
                'task_type' => $task->task_type,
            ],
        ];
    }

    private function mapExpenseCategory(string $taskType): string
    {
        return match ($taskType) {
            'oral_medication_period' => 'feed',
            'injectable', 'deworming', 'maintenance_optional' => 'medicine',
            default => 'emergency',
        };
    }

    private function toDateString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->copy()->toDateString();
        }

        return Carbon::parse((string) $value)->toDateString();
    }
}
