<?php

namespace App\Http\Controllers\President;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\PigRegistry\UpdateCycleHealthTaskRequest;
use App\Models\CycleHealthTask;
use App\Models\PigCycle;
use App\Services\PigRegistry\UpdateCycleHealthTaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PresidentCycleHealthTaskController extends Controller
{
    use RecordsAuditTrail;

    /**
     * @var list<string>
     */
    private const UNDO_ELIGIBLE_ACTIONS = [
        'complete_all',
        'partial',
        'reschedule',
        'skip',
        'not_applicable',
    ];

    public function update(
        UpdateCycleHealthTaskRequest $request,
        PigCycle $cycle,
        CycleHealthTask $healthTask,
        UpdateCycleHealthTaskService $updateCycleHealthTaskService
    ): RedirectResponse {
        if ($healthTask->batch_id !== $cycle->id) {
            abort(404);
        }

        if ($cycle->isArchived()) {
            return back()->withErrors([
                'cycle' => 'Archived cycles cannot update health tasks without reopening.',
            ]);
        }

        $validatedPayload = $request->validated();

        $action = (string) $validatedPayload['action'];
        $undoSnapshot = $this->shouldPrepareUndo($action)
            ? $this->snapshotTaskState($healthTask)
            : null;

        $updatedTask = $updateCycleHealthTaskService->handle($healthTask, $validatedPayload, $request->user());

        $this->recordAudit(
            $request,
            'cycle_health_task_updated',
            "Updated health task {$updatedTask->task_name} ({$updatedTask->status}) for cycle {$cycle->batch_code}.",
            'health_monitoring'
        );

        $redirect = $this->resolveHealthRedirect($request, $cycle);

        if (is_array($undoSnapshot) && $this->taskStateChanged($undoSnapshot, $updatedTask)) {
            $undoToken = (string) Str::uuid();

            $request->session()->put("health_task_undo.{$undoToken}", [
                'cycle_id' => $cycle->id,
                'task_id' => $updatedTask->id,
                'snapshot' => $undoSnapshot,
                'expires_at' => now()->addMinutes(15)->timestamp,
            ]);

            return $redirect
                ->with('status', $this->buildTaskActionStatusMessage($action))
                ->with('undo_task', [
                    'message' => $this->buildUndoPromptMessage($action),
                    'token' => $undoToken,
                    'cycle' => $cycle->getRouteKey(),
                    'task' => $updatedTask->getKey(),
                ]);
        }

        return $redirect
            ->with('status', 'Health task updated successfully.');
    }

    public function undo(Request $request, PigCycle $cycle, CycleHealthTask $healthTask): RedirectResponse
    {
        if ($healthTask->batch_id !== $cycle->id) {
            abort(404);
        }

        if ($cycle->isArchived()) {
            return back()->withErrors([
                'cycle' => 'Archived cycles cannot update health tasks without reopening.',
            ]);
        }

        $validated = $request->validate([
            'undo_token' => ['required', 'string'],
        ]);

        $undoKey = 'health_task_undo.'.$validated['undo_token'];
        $undoState = $request->session()->get($undoKey);

        if (! is_array($undoState)) {
            return $this->resolveHealthRedirect($request, $cycle)
                ->withErrors([
                    'undo' => 'Undo action is no longer available.',
                ]);
        }

        if ((int) ($undoState['cycle_id'] ?? 0) !== $cycle->id || (int) ($undoState['task_id'] ?? 0) !== $healthTask->id) {
            $request->session()->forget($undoKey);

            return $this->resolveHealthRedirect($request, $cycle)
                ->withErrors([
                    'undo' => 'Undo token does not match this task.',
                ]);
        }

        if ((int) ($undoState['expires_at'] ?? 0) < now()->timestamp) {
            $request->session()->forget($undoKey);

            return $this->resolveHealthRedirect($request, $cycle)
                ->withErrors([
                    'undo' => 'Undo window already expired. Please update the task manually.',
                ]);
        }

        $snapshot = $undoState['snapshot'] ?? null;

        if (! is_array($snapshot)) {
            $request->session()->forget($undoKey);

            return $this->resolveHealthRedirect($request, $cycle)
                ->withErrors([
                    'undo' => 'Unable to restore task state.',
                ]);
        }

        DB::transaction(function () use ($healthTask, $snapshot): void {
            $healthTask->forceFill([
                'status' => (string) ($snapshot['status'] ?? $healthTask->status),
                'completed_count' => (int) ($snapshot['completed_count'] ?? $healthTask->completed_count),
                'remaining_count' => (int) ($snapshot['remaining_count'] ?? $healthTask->remaining_count),
                'actual_date' => $snapshot['actual_date'] ?? null,
                'follow_up_date' => $snapshot['follow_up_date'] ?? null,
                'planned_start_date' => $snapshot['planned_start_date'] ?? $healthTask->planned_start_date,
                'remarks' => $snapshot['remarks'] ?? null,
                'completed_by' => $snapshot['completed_by'] ?? null,
            ]);

            $healthTask->save();
        });

        $request->session()->forget($undoKey);

        $this->recordAudit(
            $request,
            'cycle_health_task_update_undone',
            "Undid task action for {$healthTask->task_name} on cycle {$cycle->batch_code}.",
            'health_monitoring'
        );

        return $this->resolveHealthRedirect($request, $cycle)
            ->with('status', 'Task correction saved. Previous state restored.');
    }

    private function resolveHealthRedirect(Request $request, PigCycle $cycle): RedirectResponse
    {
        $fallbackUrl = route('health.cycles.show', $cycle);
        $referer = (string) $request->headers->get('referer', '');

        if ($this->isInternalHealthUrl($referer)) {
            return redirect()->to($referer);
        }

        $previousUrl = (string) url()->previous();

        if ($this->isInternalHealthUrl($previousUrl)) {
            return redirect()->to($previousUrl);
        }

        return redirect()->to($fallbackUrl);
    }

    private function isInternalHealthUrl(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        $appHost = parse_url(url('/'), PHP_URL_HOST);
        $candidateHost = parse_url($url, PHP_URL_HOST);

        if ($candidateHost !== null && $appHost !== null && Str::lower((string) $candidateHost) !== Str::lower((string) $appHost)) {
            return false;
        }

        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');

        return Str::startsWith($path, '/health');
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotTaskState(CycleHealthTask $task): array
    {
        return [
            'status' => (string) $task->status,
            'completed_count' => (int) $task->completed_count,
            'remaining_count' => (int) $task->remaining_count,
            'actual_date' => $this->normalizeDateValue($task->actual_date),
            'follow_up_date' => $this->normalizeDateValue($task->follow_up_date),
            'planned_start_date' => $this->normalizeDateValue($task->planned_start_date),
            'remarks' => $task->remarks,
            'completed_by' => $task->completed_by,
        ];
    }

    private function normalizeDateValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->toDateString();
        }

        return (string) $value;
    }

    private function shouldPrepareUndo(string $action): bool
    {
        return in_array($action, self::UNDO_ELIGIBLE_ACTIONS, true);
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function taskStateChanged(array $snapshot, CycleHealthTask $task): bool
    {
        return (string) ($snapshot['status'] ?? '') !== (string) $task->status
            || (int) ($snapshot['completed_count'] ?? -1) !== (int) $task->completed_count
            || (int) ($snapshot['remaining_count'] ?? -1) !== (int) $task->remaining_count
            || (string) ($snapshot['actual_date'] ?? '') !== (string) ($this->normalizeDateValue($task->actual_date) ?? '')
            || (string) ($snapshot['follow_up_date'] ?? '') !== (string) ($this->normalizeDateValue($task->follow_up_date) ?? '')
            || (string) ($snapshot['planned_start_date'] ?? '') !== (string) ($this->normalizeDateValue($task->planned_start_date) ?? '')
            || (string) ($snapshot['remarks'] ?? '') !== (string) ($task->remarks ?? '')
            || (int) ($snapshot['completed_by'] ?? 0) !== (int) ($task->completed_by ?? 0);
    }

    private function buildTaskActionStatusMessage(string $action): string
    {
        return match ($action) {
            'complete_all' => 'Task marked as Completed.',
            'partial' => 'Task marked as Partially Completed.',
            'reschedule' => 'Task marked as Rescheduled.',
            'skip' => 'Task marked as Skipped.',
            'not_applicable' => 'Task marked as Not Applicable.',
            default => 'Health task updated successfully.',
        };
    }

    private function buildUndoPromptMessage(string $action): string
    {
        return match ($action) {
            'complete_all' => 'Task marked as Completed. Undo?',
            'partial' => 'Task marked as Partially Completed. Undo?',
            'reschedule' => 'Task marked as Rescheduled. Undo?',
            'skip' => 'Task marked as Skipped. Undo?',
            'not_applicable' => 'Task marked as Not Applicable. Undo?',
            default => 'Task updated successfully. Undo?',
        };
    }
}
