<?php

namespace App\Services\PigRegistry;

use App\Models\CycleHealthTask;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateCycleHealthTaskService
{
    public function __construct(
        private readonly CycleHealthTaskStatusResolver $statusResolver
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(CycleHealthTask $task, array $payload, User $actor): CycleHealthTask
    {
        return DB::transaction(function () use ($task, $payload, $actor): CycleHealthTask {
            $action = (string) $payload['action'];
            $completionCap = $this->resolveCompletionCap($task);

            if (array_key_exists('remarks', $payload)) {
                $task->remarks = $payload['remarks'] ?: null;
            }

            switch ($action) {
                case 'complete_all':
                    $task->completed_count = $completionCap;
                    $task->actual_date = $payload['actual_date'] ?? now()->toDateString();
                    $task->completed_by = $actor->id;
                    break;

                case 'partial':
                    $task->completed_count = min((int) $payload['completed_count'], $completionCap);
                    $task->actual_date = $payload['actual_date'] ?? now()->toDateString();
                    $task->follow_up_date = $payload['follow_up_date'] ?? $task->follow_up_date;
                    $task->completed_by = $actor->id;
                    break;

                case 'reschedule':
                    if (! empty($payload['planned_start_date'])) {
                        $task->planned_start_date = $payload['planned_start_date'];
                    }

                    if (! empty($payload['follow_up_date'])) {
                        $task->follow_up_date = $payload['follow_up_date'];
                    }

                    $task->status = 'rescheduled';
                    break;

                case 'skip':
                    $task->status = 'skipped';
                    break;

                case 'not_applicable':
                    $task->status = 'not_applicable';
                    break;
            }

            $task->save();

            return $this->statusResolver->refreshTask($task);
        });
    }

    private function resolveCompletionCap(CycleHealthTask $task): int
    {
        $targetCount = max(0, (int) $task->target_count);
        $cycleCurrentCount = (int) ($task->cycle()->value('current_count') ?? 0);

        return max(0, min($targetCount, $cycleCurrentCount));
    }
}
