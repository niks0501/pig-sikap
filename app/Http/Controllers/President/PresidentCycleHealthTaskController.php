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

    private const UNDO_SESSION_KEY = 'health_task_undo';

    private const UNDO_EXPIRATION_MINUTES = 15;

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
                'cycle' => 'Archived cycles are final and cannot update health tasks.',
            ]);
        }

        $validatedPayload = $request->validated();

        $action = (string) $validatedPayload['action'];
        $beforeSnapshot = $this->snapshotTaskState($healthTask);
        $undoSnapshot = $this->shouldPrepareUndo($action)
            ? $beforeSnapshot
            : null;

        $updatedTask = $updateCycleHealthTaskService->handle($healthTask, $validatedPayload, $request->user());

        $this->recordAudit(
            $request,
            'cycle_health_task_updated',
            "Updated health task {$updatedTask->task_name} ({$updatedTask->status}) for cycle {$cycle->batch_code}.",
            'health_monitoring',
            [
                'cycle_id' => $cycle->id,
                'cycle_batch_code' => $cycle->batch_code,
                'task_id' => $updatedTask->id,
                'task_name' => $updatedTask->task_name,
                'task_type' => $updatedTask->task_type,
                'requested_action' => $action,
                'before_status' => (string) ($beforeSnapshot['status'] ?? ''),
                'after_status' => (string) $updatedTask->status,
                'before_completed_count' => (int) ($beforeSnapshot['completed_count'] ?? 0),
                'after_completed_count' => (int) $updatedTask->completed_count,
                'before_remaining_count' => (int) ($beforeSnapshot['remaining_count'] ?? 0),
                'after_remaining_count' => (int) $updatedTask->remaining_count,
                'actual_date' => $this->normalizeDateValue($updatedTask->actual_date),
                'follow_up_date' => $this->normalizeDateValue($updatedTask->follow_up_date),
                'planned_start_date' => $this->normalizeDateValue($updatedTask->planned_start_date),
            ]
        );

        $redirect = $this->resolveHealthRedirect($request, $cycle);

        if (is_array($undoSnapshot) && $this->taskStateChanged($undoSnapshot, $updatedTask)) {
            $undoToken = (string) Str::uuid();
            $undoState = $this->getUndoTaskState($request);
            $undoState[$this->hashUndoToken($undoToken)] = [
                'cycle_id' => $cycle->id,
                'task_id' => $updatedTask->id,
                'snapshot' => $undoSnapshot,
                'expires_at' => now()->addMinutes(self::UNDO_EXPIRATION_MINUTES)->timestamp,
                'used_at' => null,
            ];

            $request->session()->put(self::UNDO_SESSION_KEY, $undoState);

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
                'cycle' => 'Archived cycles are final and cannot update health tasks.',
            ]);
        }

        $validated = $request->validate([
            'undo_token' => ['required', 'uuid'],
        ]);

        $undoTokenHash = $this->hashUndoToken($validated['undo_token']);
        $undoState = $this->getUndoTaskState($request);
        $undoEntry = $undoState[$undoTokenHash] ?? null;

        if (! is_array($undoEntry)) {
            return $this->resolveHealthRedirect($request, $cycle)
                ->withErrors([
                    'undo' => 'Undo action is no longer available.',
                ]);
        }

        if ((int) ($undoEntry['expires_at'] ?? 0) < now()->timestamp) {
            unset($undoState[$undoTokenHash]);
            $request->session()->put(self::UNDO_SESSION_KEY, $undoState);

            return $this->resolveHealthRedirect($request, $cycle)
                ->withErrors([
                    'undo' => 'Undo window already expired. Please update the task manually.',
                ]);
        }

        if (($undoEntry['used_at'] ?? null) !== null) {
            return $this->resolveHealthRedirect($request, $cycle)
                ->withErrors([
                    'undo' => 'Undo token has already been used.',
                ]);
        }

        if ((int) ($undoEntry['cycle_id'] ?? 0) !== $cycle->id || (int) ($undoEntry['task_id'] ?? 0) !== $healthTask->id) {
            return $this->resolveHealthRedirect($request, $cycle)
                ->withErrors([
                    'undo' => 'Undo token does not match this task.',
                ]);
        }

        $snapshot = $undoEntry['snapshot'] ?? null;

        if (! is_array($snapshot)) {
            return $this->resolveHealthRedirect($request, $cycle)
                ->withErrors([
                    'undo' => 'Unable to restore task state.',
                ]);
        }

            $stateBeforeUndo = $this->snapshotTaskState($healthTask);

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

        $undoState[$undoTokenHash]['used_at'] = now()->timestamp;
        $request->session()->put(self::UNDO_SESSION_KEY, $undoState);

        $this->recordAudit(
            $request,
            'cycle_health_task_update_undone',
            "Undid task action for {$healthTask->task_name} on cycle {$cycle->batch_code}.",
            'health_monitoring',
            [
                'cycle_id' => $cycle->id,
                'cycle_batch_code' => $cycle->batch_code,
                'task_id' => $healthTask->id,
                'task_name' => $healthTask->task_name,
                'task_type' => $healthTask->task_type,
                'undo_token_hash' => $undoTokenHash,
                'status_before_undo' => (string) ($stateBeforeUndo['status'] ?? ''),
                'status_after_undo' => (string) $healthTask->status,
                'completed_count_before_undo' => (int) ($stateBeforeUndo['completed_count'] ?? 0),
                'completed_count_after_undo' => (int) $healthTask->completed_count,
                'remaining_count_before_undo' => (int) ($stateBeforeUndo['remaining_count'] ?? 0),
                'remaining_count_after_undo' => (int) $healthTask->remaining_count,
            ]
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

        $candidate = parse_url($url);
        $appUrl = parse_url((string) config('app.url'));

        if (! is_array($candidate) || ! is_array($appUrl)) {
            return false;
        }

        if (! $this->isMatchingUrlComponent($candidate['scheme'] ?? null, $appUrl['scheme'] ?? null)) {
            return false;
        }

        if (! $this->isMatchingUrlComponent($candidate['host'] ?? null, $appUrl['host'] ?? null)) {
            return false;
        }

        if ($this->normalizeUrlPort($candidate['scheme'] ?? null, $candidate['port'] ?? null) !== $this->normalizeUrlPort($appUrl['scheme'] ?? null, $appUrl['port'] ?? null)) {
            return false;
        }

        $candidatePath = $this->normalizeUrlPath((string) ($candidate['path'] ?? '/'));
        $appPath = $this->normalizeUrlPath((string) ($appUrl['path'] ?? '/'));
        $healthPath = $appPath === '/' ? '/health' : rtrim($appPath, '/').'/health';

        return $candidatePath === $healthPath || Str::startsWith($candidatePath, $healthPath.'/');
    }

    private function isMatchingUrlComponent(mixed $candidate, mixed $expected): bool
    {
        if (! is_string($candidate) || ! is_string($expected) || $candidate === '' || $expected === '') {
            return false;
        }

        return Str::lower($candidate) === Str::lower($expected);
    }

    private function normalizeUrlPort(mixed $scheme, mixed $port): int
    {
        if (is_numeric($port)) {
            return (int) $port;
        }

        return match (Str::lower((string) $scheme)) {
            'http' => 80,
            'https' => 443,
            default => 0,
        };
    }

    private function normalizeUrlPath(string $path): string
    {
        $normalized = '/' . ltrim($path, '/');

        // When $path is exactly '/', prefixing '/' to the ltrim() result produces '//'.
        // Collapse that back to the root path before applying the general trailing-slash
        // normalization so '/' and equivalent root-path inputs are treated consistently.
        return $normalized === '//' ? '/' : (rtrim($normalized, '/') ?: '/');
    }

    private function hashUndoToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getUndoTaskState(Request $request): array
    {
        $undoState = $request->session()->get(self::UNDO_SESSION_KEY, []);

        if (! is_array($undoState)) {
            return [];
        }

        $now = now()->timestamp;

        return array_filter($undoState, function ($entry) use ($now): bool {
            return is_array($entry) && (int) ($entry['expires_at'] ?? 0) >= $now;
        });
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
