<?php

namespace App\Services\PigRegistry;

use App\Models\CycleHealthTask;

class CycleHealthExpenseDraftBuilder
{
    public function __construct(
        private readonly CycleHealthDateNormalizer $dateNormalizer
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function fromTask(CycleHealthTask $task): array
    {
        return [
            'batch_id' => $task->batch_id,
            'category' => $this->mapExpenseCategory($task->task_type),
            'expense_date' => $this->dateNormalizer->toDateString($task->actual_date) ?? $this->dateNormalizer->toDateString($task->planned_start_date),
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
}
