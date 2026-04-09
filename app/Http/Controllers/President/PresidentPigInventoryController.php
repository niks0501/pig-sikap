<?php

namespace App\Http\Controllers\President;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\PigRegistry\StorePigBatchRequest;
use App\Http\Requests\PigRegistry\UpdatePigBatchRequest;
use App\Models\Pig;
use App\Models\PigBatch;
use App\Models\PigBatchAdjustment;
use App\Models\PigBatchStatusHistory;
use App\Models\PigBreeder;
use App\Models\User;
use App\Services\PigRegistry\CreatePigBatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PresidentPigInventoryController extends Controller
{
    use RecordsAuditTrail;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $stage = trim((string) $request->query('stage', ''));
        $status = trim((string) $request->query('status', ''));
        $breederId = trim((string) $request->query('breeder', ''));
        $caretakerId = trim((string) $request->query('caretaker', ''));
        $scope = trim((string) $request->query('scope', 'all'));

        $query = PigBatch::query()->with([
            'breeder:id,breeder_code,name_or_tag',
            'caretaker:id,name',
        ]);

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('batch_code', 'like', "%{$search}%")
                    ->orWhereHas('breeder', function ($breederQuery) use ($search): void {
                        $breederQuery->where('breeder_code', 'like', "%{$search}%")
                            ->orWhere('name_or_tag', 'like', "%{$search}%");
                    })
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

        if ($breederId !== '') {
            $query->where('breeder_id', $breederId);
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

        $batches = $query
            ->orderByDesc('updated_at')
            ->paginate(12)
            ->withQueryString();

        return view('batches.index', [
            'batches' => $batches,
            'filters' => [
                'search' => $search,
                'stage' => $stage,
                'status' => $status,
                'breeder' => $breederId,
                'caretaker' => $caretakerId,
                'scope' => $scope,
            ],
            'summary' => $this->summary(),
            'recentUpdates' => $this->recentUpdates(),
            'stages' => PigBatch::STAGES,
            'statuses' => PigBatch::STATUSES,
            'breeders' => PigBreeder::query()->orderBy('name_or_tag')->get(['id', 'breeder_code', 'name_or_tag']),
            'caretakers' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        return view('batches.create', [
            'batchCode' => $this->nextBatchCode(),
            'stages' => PigBatch::STAGES,
            'statuses' => PigBatch::STATUSES,
            'breeders' => PigBreeder::query()->orderBy('name_or_tag')->get(['id', 'breeder_code', 'name_or_tag']),
            'caretakers' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StorePigBatchRequest $request, CreatePigBatchService $createPigBatchService): RedirectResponse
    {
        $batch = $createPigBatchService->handle($request->validated(), $request->user());

        $this->recordAudit(
            $request,
            'batch_created',
            "Created batch {$batch->batch_code} with {$batch->current_count} pigs."
        );

        return redirect()
            ->route('batches.show', $batch)
            ->with('status', 'Batch was created successfully.');
    }

    public function show(PigBatch $batch): View
    {
        $batch->load([
            'breeder:id,breeder_code,name_or_tag',
            'caretaker:id,name',
            'pigs' => fn ($query) => $query->orderBy('pig_no'),
            'adjustments.createdBy:id,name',
            'statusHistories.changedBy:id,name',
        ]);

        return view('batches.show', [
            'batch' => $batch,
            'adjustmentTypes' => PigBatchAdjustment::ADJUSTMENT_TYPES,
            'adjustmentReasons' => PigBatchAdjustment::REASONS,
            'stages' => PigBatch::STAGES,
            'statuses' => PigBatch::STATUSES,
            'pigStatuses' => Pig::STATUSES,
            'sexOptions' => Pig::SEX_OPTIONS,
        ]);
    }

    public function edit(PigBatch $batch): View|RedirectResponse
    {
        if ($batch->isArchived()) {
            return redirect()
                ->route('batches.show', $batch)
                ->withErrors([
                    'batch' => 'Archived batches can no longer be edited in the regular form.',
                ]);
        }

        return view('batches.edit', [
            'batch' => $batch,
            'stages' => PigBatch::STAGES,
            'statuses' => PigBatch::STATUSES,
            'breeders' => PigBreeder::query()->orderBy('name_or_tag')->get(['id', 'breeder_code', 'name_or_tag']),
            'caretakers' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdatePigBatchRequest $request, PigBatch $batch): RedirectResponse
    {
        if ($batch->isArchived()) {
            return back()->withErrors([
                'batch' => 'Archived batches cannot be edited without reopening.',
            ]);
        }

        $oldStage = $batch->stage;
        $oldStatus = $batch->status;

        $batch->update([
            ...$request->validated(),
            'last_reviewed_at' => now(),
        ]);

        if ($oldStage !== $batch->stage || $oldStatus !== $batch->status) {
            PigBatchStatusHistory::create([
                'batch_id' => $batch->id,
                'old_stage' => $oldStage,
                'new_stage' => $batch->stage,
                'old_status' => $oldStatus,
                'new_status' => $batch->status,
                'remarks' => 'Updated from edit batch form.',
                'changed_by' => $request->user()->id,
            ]);

            $this->recordAudit(
                $request,
                'batch_status_updated',
                "Updated stage/status for batch {$batch->batch_code} to {$batch->stage} / {$batch->status}."
            );
        } else {
            $this->recordAudit(
                $request,
                'batch_updated',
                "Updated batch {$batch->batch_code} profile details."
            );
        }

        return redirect()
            ->route('batches.show', $batch)
            ->with('status', 'Batch details were updated.');
    }

    public function archive(Request $request, PigBatch $batch): RedirectResponse
    {
        if (! $batch->isArchived()) {
            $oldStage = $batch->stage;
            $oldStatus = $batch->status;

            $batch->update([
                'stage' => 'Completed',
                'status' => 'Closed',
                'last_reviewed_at' => now(),
            ]);

            PigBatchStatusHistory::create([
                'batch_id' => $batch->id,
                'old_stage' => $oldStage,
                'new_stage' => 'Completed',
                'old_status' => $oldStatus,
                'new_status' => 'Closed',
                'remarks' => (string) $request->input('remarks', 'Batch archived from Pig Registry.'),
                'changed_by' => $request->user()->id,
            ]);

            $this->recordAudit(
                $request,
                'batch_archived',
                "Archived batch {$batch->batch_code}."
            );
        }

        return redirect()
            ->route('batches.archived')
            ->with('status', 'Batch moved to archived records.');
    }

    public function archived(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $query = PigBatch::query()
            ->with(['breeder:id,breeder_code,name_or_tag', 'caretaker:id,name'])
            ->archivedRecords();

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('batch_code', 'like', "%{$search}%")
                    ->orWhereHas('breeder', function ($breederQuery) use ($search): void {
                        $breederQuery->where('breeder_code', 'like', "%{$search}%")
                            ->orWhere('name_or_tag', 'like', "%{$search}%");
                    });
            });
        }

        return view('batches.archived', [
            'batches' => $query->latest('updated_at')->paginate(12)->withQueryString(),
            'search' => $search,
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function summary(): array
    {
        return [
            'active_batches' => PigBatch::query()->activeRecords()->count(),
            'total_piglets' => (int) PigBatch::query()->where('stage', 'Piglet')->sum('current_count'),
            'total_breeders' => PigBreeder::query()->count(),
            'total_fatteners' => (int) PigBatch::query()->where('stage', 'Fattening')->sum('current_count'),
            'total_sick' => Pig::query()->where('status', 'Sick')->count(),
            'total_deceased' => Pig::query()->where('status', 'Deceased')->count(),
            'ready_for_sale_batches' => PigBatch::query()->where('status', 'Ready for Sale')->count(),
        ];
    }

    private function nextBatchCode(): string
    {
        $latestCode = (string) PigBatch::query()->withTrashed()->latest('id')->value('batch_code');

        if (preg_match('/(\d+)$/', $latestCode, $matches) === 1) {
            $nextNumber = (int) $matches[1] + 1;

            return 'B-'.str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
        }

        return 'B-001';
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function recentUpdates(): Collection
    {
        $statusRows = PigBatchStatusHistory::query()
            ->with(['batch:id,batch_code', 'changedBy:id,name'])
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(fn (PigBatchStatusHistory $history) => [
                'type' => 'status',
                'batch_code' => $history->batch?->batch_code,
                'description' => "Stage/status updated to {$history->new_stage} / {$history->new_status}",
                'actor' => $history->changedBy?->name,
                'created_at' => $history->created_at,
            ]);

        $adjustmentRows = PigBatchAdjustment::query()
            ->with(['batch:id,batch_code', 'createdBy:id,name'])
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(fn (PigBatchAdjustment $adjustment) => [
                'type' => 'adjustment',
                'batch_code' => $adjustment->batch?->batch_code,
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
