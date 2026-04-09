<?php

namespace App\Http\Controllers\President;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\PigRegistry\StorePigRequest;
use App\Http\Requests\PigRegistry\UpdatePigRequest;
use App\Models\Pig;
use App\Models\PigBatch;
use App\Models\PigBatchAdjustment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PresidentPigProfileController extends Controller
{
    use RecordsAuditTrail;

    public function index(PigBatch $batch): View
    {
        $batch->load(['breeder:id,breeder_code,name_or_tag', 'pigs' => fn ($query) => $query->orderBy('pig_no')]);

        return view('batches.pigs', [
            'batch' => $batch,
            'pigStatuses' => Pig::STATUSES,
            'sexOptions' => Pig::SEX_OPTIONS,
        ]);
    }

    public function store(StorePigRequest $request, PigBatch $batch): RedirectResponse
    {
        if ($batch->isArchived()) {
            return back()->withErrors([
                'batch' => 'Archived batches cannot accept new pig profiles.',
            ]);
        }

        /** @var array{pig: Pig, adjustment: PigBatchAdjustment|null} $result */
        $result = DB::transaction(function () use ($request, $batch): array {
            $pig = $batch->pigs()->create([
                ...$request->validated(),
                'created_by' => $request->user()->id,
            ]);

            if (! $batch->has_pig_profiles) {
                $batch->update([
                    'has_pig_profiles' => true,
                    'last_reviewed_at' => now(),
                ]);
            }

            $adjustment = $this->syncBatchCountForPigStatusTransition(
                $batch,
                null,
                (string) $pig->status,
                (int) $request->user()->id,
                (int) $pig->pig_no
            );

            return [
                'pig' => $pig,
                'adjustment' => $adjustment,
            ];
        });

        $pig = $result['pig'];
        $adjustment = $result['adjustment'];

        if ($adjustment !== null) {
            $this->recordAudit(
                $request,
                'batch_count_adjusted',
                "Auto-adjusted {$batch->batch_code} from {$adjustment->quantity_before} to {$adjustment->quantity_after} via pig #{$pig->pig_no} status Counted -> {$pig->status}."
            );
        }

        $this->recordAudit(
            $request,
            'pig_profile_created',
            "Created pig profile #{$pig->pig_no} in batch {$batch->batch_code}."
        );

        return redirect()
            ->route('batches.show', $batch)
            ->with('status', 'Pig profile added successfully.');
    }

    public function update(UpdatePigRequest $request, PigBatch $batch, Pig $pig): RedirectResponse
    {
        if ($pig->batch_id !== $batch->id) {
            abort(404);
        }

        if ($batch->isArchived()) {
            return back()->withErrors([
                'batch' => 'Archived batches cannot modify pig profiles.',
            ]);
        }

        $previousStatus = (string) $pig->status;

        /** @var array{pig: Pig, adjustment: PigBatchAdjustment|null} $result */
        $result = DB::transaction(function () use ($request, $batch, $pig, $previousStatus): array {
            $pig->update($request->validated());

            $adjustment = $this->syncBatchCountForPigStatusTransition(
                $batch,
                $previousStatus,
                (string) $pig->status,
                (int) $request->user()->id,
                (int) $pig->pig_no
            );

            return [
                'pig' => $pig,
                'adjustment' => $adjustment,
            ];
        });

        $pig = $result['pig'];
        $adjustment = $result['adjustment'];

        if ($adjustment !== null) {
            $this->recordAudit(
                $request,
                'batch_count_adjusted',
                "Auto-adjusted {$batch->batch_code} from {$adjustment->quantity_before} to {$adjustment->quantity_after} via pig #{$pig->pig_no} status {$previousStatus} -> {$pig->status}."
            );
        }

        $this->recordAudit(
            $request,
            'pig_profile_updated',
            "Updated pig profile #{$pig->pig_no} in batch {$batch->batch_code}."
        );

        return redirect()
            ->route('batches.show', $batch)
            ->with('status', 'Pig profile updated successfully.');
    }

    public function destroy(Request $request, PigBatch $batch, Pig $pig): RedirectResponse
    {
        if ($pig->batch_id !== $batch->id) {
            abort(404);
        }

        if ($batch->isArchived()) {
            return back()->withErrors([
                'batch' => 'Archived batches cannot modify pig profiles.',
            ]);
        }

        $pigNo = (int) $pig->pig_no;
        $statusBeforeDelete = (string) $pig->status;

        /** @var array{adjustment: PigBatchAdjustment|null} $result */
        $result = DB::transaction(function () use ($request, $batch, $pig): array {
            $adjustment = null;

            if (Pig::statusCountsTowardBatch((string) $pig->status)) {
                $adjustment = $this->syncBatchCountForPigStatusTransition(
                    $batch,
                    (string) $pig->status,
                    'Deleted',
                    (int) $request->user()->id,
                    (int) $pig->pig_no
                );
            }

            $pig->delete();

            return [
                'adjustment' => $adjustment,
            ];
        });

        if ($result['adjustment'] !== null) {
            $adjustment = $result['adjustment'];

            $this->recordAudit(
                $request,
                'batch_count_adjusted',
                "Auto-adjusted {$batch->batch_code} from {$adjustment->quantity_before} to {$adjustment->quantity_after} via pig #{$pigNo} deletion."
            );
        }

        $this->recordAudit(
            $request,
            'pig_profile_deleted',
            "Deleted pig profile #{$pigNo} ({$statusBeforeDelete}) in batch {$batch->batch_code}."
        );

        return redirect()
            ->route('batches.pigs.index', $batch)
            ->with('status', 'Pig profile deleted successfully.');
    }

    private function syncBatchCountForPigStatusTransition(
        PigBatch $batch,
        ?string $previousStatus,
        string $newStatus,
        int $actorId,
        int $pigNo
    ): ?PigBatchAdjustment {
        $wasCounted = Pig::statusCountsTowardBatch($previousStatus);
        $isCounted = Pig::statusCountsTowardBatch($newStatus);

        if ($wasCounted === $isCounted) {
            return null;
        }

        $delta = $isCounted ? 1 : -1;

        $lockedBatch = PigBatch::query()
            ->whereKey($batch->id)
            ->lockForUpdate()
            ->firstOrFail();

        $before = (int) $lockedBatch->current_count;
        $after = $before + $delta;

        if ($after < 0) {
            throw ValidationException::withMessages([
                'status' => 'Status change cannot reduce batch count below zero.',
            ]);
        }

        $lockedBatch->update([
            'current_count' => $after,
            'last_reviewed_at' => now(),
        ]);

        return PigBatchAdjustment::create([
            'batch_id' => $lockedBatch->id,
            'adjustment_type' => $delta > 0 ? 'increase' : 'decrease',
            'quantity_before' => $before,
            'quantity_change' => $delta,
            'quantity_after' => $after,
            'reason' => $delta > 0
                ? Pig::autoIncreaseReasonForStatus($previousStatus)
                : Pig::autoDecreaseReasonForStatus($newStatus),
            'remarks' => "Auto-adjusted from pig profile #{$pigNo}: ".($previousStatus ?? 'Counted')." -> {$newStatus}.",
            'created_by' => $actorId,
        ]);
    }
}
