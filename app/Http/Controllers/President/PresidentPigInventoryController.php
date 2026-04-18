<?php

namespace App\Http\Controllers\President;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\PigRegistry\StorePigCycleRequest;
use App\Http\Requests\PigRegistry\UpdatePigCycleRequest;
use App\Models\Pig;
use App\Models\PigCycle;
use App\Models\PigCycleAdjustment;
use App\Models\PigCycleStatusHistory;
use App\Models\User;
use App\Services\PigRegistry\AnalyzePigCycleService;
use App\Services\PigRegistry\CycleSummaryService;
use App\Services\PigRegistry\CreatePigCycleService;
use App\Services\PigRegistry\UpdatePigCycleStatusService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PresidentPigInventoryController extends Controller
{
    use RecordsAuditTrail;

    public function index(Request $request, CycleSummaryService $cycleSummaryService): View|JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $stage = trim((string) $request->query('stage', ''));
        $status = trim((string) $request->query('status', ''));
        $caretakerId = trim((string) $request->query('caretaker', ''));
        $scope = trim((string) $request->query('scope', 'all'));

        $query = PigCycle::query()->with([
            'caretaker:id,name',
        ]);

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('batch_code', 'like', "%{$search}%")
                    ->orWhereHas('caretaker', function ($caretakerQuery) use ($search): void {
                        $caretakerQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($stage !== '') {
            $query->where('stage', $stage);
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($caretakerId !== '') {
            $query->where('caretaker_user_id', $caretakerId);
        }

        if ($scope === 'active') {
            $query->activeRecords();
        }

        if ($scope === 'archived') {
            $query->archivedRecords();
        }

        $cycles = $query
            ->orderByDesc('updated_at')
            ->paginate(12)
            ->withQueryString();

        $summary = $cycleSummaryService->forDashboard();
        $recentUpdates = $this->recentUpdates();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $cycles->items(),
                'meta' => $this->paginationMeta($cycles),
                'summary' => $summary,
                'recent_updates' => $recentUpdates->values(),
            ]);
        }

        return view('cycles.index', [
            'cycles' => $cycles,
            'filters' => [
                'search' => $search,
                'stage' => $stage,
                'status' => $status,
                'caretaker' => $caretakerId,
                'scope' => $scope,
            ],
            'summary' => $summary,
            'recentUpdates' => $recentUpdates,
            'stages' => PigCycle::STAGES,
            'statuses' => PigCycle::STATUSES,
            'caretakers' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        return view('cycles.create', [
            'cycleCode' => $this->nextCycleCode(),
            'stages' => PigCycle::STAGES,
            'statuses' => PigCycle::STATUSES,
            'caretakers' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StorePigCycleRequest $request, CreatePigCycleService $createPigCycleService): RedirectResponse
    {
        $cycle = $createPigCycleService->handle($request->validated(), $request->user());

        $this->recordAudit(
            $request,
            'cycle_created',
            "Created cycle {$cycle->batch_code} with {$cycle->current_count} pigs."
        );

        return redirect()
            ->route('cycles.show', $cycle)
            ->with('status', 'Cycle was created successfully.');
    }

    public function show(PigCycle $cycle, AnalyzePigCycleService $analyzePigCycleService): View
    {
        $cycle->load([
            'caretaker:id,name',
            'pigs' => fn ($query) => $query->orderBy('pig_no'),
            'adjustments.createdBy:id,name',
            'statusHistories.changedBy:id,name',
            'healthTemplate:id,name,code',
            'healthTasks' => fn ($query) => $query->orderBy('planned_start_date')->orderBy('id'),
            'healthIncidents' => fn ($query) => $query->latest('date_reported')->latest('id'),
        ]);

        $automation = $analyzePigCycleService->handle($cycle);

        return view('cycles.show', [
            'cycle' => $cycle,
            'adjustmentTypes' => PigCycleAdjustment::ADJUSTMENT_TYPES,
            'adjustmentReasons' => PigCycleAdjustment::REASONS,
            'stages' => PigCycle::STAGES,
            'statuses' => PigCycle::STATUSES,
            'pigStatuses' => Pig::STATUSES,
            'sexOptions' => Pig::SEX_OPTIONS,
            'automation' => $automation,
            'reopenRoute' => route('cycles.reopen', $cycle),
        ]);
    }

    public function edit(PigCycle $cycle): View|RedirectResponse
    {
        if ($cycle->isArchived()) {
            return redirect()
                ->route('cycles.show', $cycle)
                ->withErrors([
                    'cycle' => 'Archived cycles can no longer be edited in the regular form.',
                ]);
        }

        return view('cycles.edit', [
            'cycle' => $cycle,
            'stages' => PigCycle::STAGES,
            'statuses' => PigCycle::STATUSES,
            'caretakers' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(
        UpdatePigCycleRequest $request,
        PigCycle $cycle,
        UpdatePigCycleStatusService $updatePigCycleStatusService
    ): RedirectResponse
    {
        if ($cycle->isArchived()) {
            return back()->withErrors([
                'cycle' => 'Archived cycles cannot be edited without reopening.',
            ]);
        }

        $validated = $request->validated();
        $oldStage = $cycle->stage;
        $oldStatus = $cycle->status;

        $cycle->update([
            ...Arr::except($validated, ['stage', 'status']),
            'last_reviewed_at' => now(),
        ]);

        if ($oldStage !== (string) $validated['stage'] || $oldStatus !== (string) $validated['status']) {
            $history = $updatePigCycleStatusService->handle(
                $cycle,
                [
                    'new_stage' => (string) $validated['stage'],
                    'new_status' => (string) $validated['status'],
                    'remarks' => 'Updated from edit cycle form.',
                ],
                $request->user(),
                [
                    'transition_origin' => 'cycle_edit_form',
                    'transition_type' => 'status_update',
                ]
            );

            $this->recordAudit(
                $request,
                'cycle_status_updated',
                "Updated stage/status for cycle {$cycle->batch_code} to {$history->new_stage} / {$history->new_status}.",
                'pig_registry',
                [
                    'cycle_id' => $cycle->id,
                    'history_id' => $history->id,
                ]
            );
        } else {
            $this->recordAudit(
                $request,
                'cycle_updated',
                "Updated cycle {$cycle->batch_code} profile details."
            );
        }

        return redirect()
            ->route('cycles.show', $cycle)
            ->with('status', 'Cycle details were updated.');
    }

    public function archive(
        Request $request,
        PigCycle $cycle,
        UpdatePigCycleStatusService $updatePigCycleStatusService
    ): RedirectResponse
    {
        if (! $cycle->isArchived()) {
            $history = $updatePigCycleStatusService->handle(
                $cycle,
                [
                    'new_stage' => 'Completed',
                    'new_status' => 'Closed',
                    'remarks' => (string) $request->input('remarks', 'Cycle archived from Cycles module.'),
                ],
                $request->user(),
                [
                    'transition_origin' => 'cycle_archive_endpoint',
                    'transition_type' => 'archive',
                ]
            );

            $this->recordAudit(
                $request,
                'cycle_archived',
                "Archived cycle {$cycle->batch_code}.",
                'pig_registry',
                [
                    'cycle_id' => $cycle->id,
                    'history_id' => $history->id,
                ]
            );
        }

        return redirect()
            ->route('cycles.archived')
            ->with('status', 'Cycle moved to archived records.');
    }

    public function destroy(Request $request, PigCycle $cycle): RedirectResponse
    {
        if (! $cycle->isArchived()) {
            return back()->withErrors([
                'cycle' => 'Only archived cycles can be deleted.',
            ]);
        }

        $cycleCode = $cycle->batch_code;

        DB::transaction(function () use ($cycle): void {
            $cycle->forceDelete();
        });

        $this->recordAudit(
            $request,
            'cycle_deleted',
            "Deleted archived cycle {$cycleCode}."
        );

        return redirect()
            ->route('cycles.archived')
            ->with('status', "Archived cycle {$cycleCode} deleted successfully.");
    }

    public function archived(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        $query = PigCycle::query()
            ->with(['caretaker:id,name'])
            ->archivedRecords();

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('batch_code', 'like', "%{$search}%")
                    ->orWhereHas('caretaker', function ($caretakerQuery) use ($search): void {
                        $caretakerQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $cycles = $query->latest('updated_at')->paginate(12)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $cycles->items(),
                'meta' => $this->paginationMeta($cycles),
            ]);
        }

        return view('cycles.archived', [
            'cycles' => $cycles,
            'search' => $search,
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    private function nextCycleCode(): string
    {
        $latestCode = (string) PigCycle::query()->withTrashed()->latest('id')->value('batch_code');

        if (preg_match('/(\d+)$/', $latestCode, $matches) === 1) {
            $nextNumber = (int) $matches[1] + 1;

            return 'C-'.str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
        }

        return 'C-001';
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function recentUpdates(): Collection
    {
        $statusRows = PigCycleStatusHistory::query()
            ->with(['cycle:id,batch_code', 'changedBy:id,name'])
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(fn (PigCycleStatusHistory $history) => [
                'type' => 'status',
                'cycle_code' => $history->cycle?->batch_code,
                'description' => "Stage/status updated to {$history->new_stage} / {$history->new_status}",
                'actor' => $history->changedBy?->name,
                'created_at' => $history->created_at,
            ]);

        $adjustmentRows = PigCycleAdjustment::query()
            ->with(['cycle:id,batch_code', 'createdBy:id,name'])
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(fn (PigCycleAdjustment $adjustment) => [
                'type' => 'adjustment',
                'cycle_code' => $adjustment->cycle?->batch_code,
                'description' => "Count adjusted from {$adjustment->quantity_before} to {$adjustment->quantity_after}",
                'actor' => $adjustment->createdBy?->name,
                'created_at' => $adjustment->created_at,
            ]);

        return $statusRows
            ->merge($adjustmentRows)
            ->sortByDesc('created_at')
            ->values()
            ->take(8);
    }
}
