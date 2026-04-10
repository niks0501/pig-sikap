<?php

namespace App\Http\Controllers\President;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\PigRegistry\StorePigRequest;
use App\Http\Requests\PigRegistry\UpdatePigRequest;
use App\Models\Pig;
use App\Models\PigCycle;
use App\Models\PigCycleAdjustment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PresidentPigProfileController extends Controller
{
    use RecordsAuditTrail;

    public function index(PigCycle $cycle): View
    {
        $cycle->load(['caretaker:id,name', 'pigs' => fn ($query) => $query->orderBy('pig_no')]);

        return view('cycles.pigs', [
            'cycle' => $cycle,
            'pigStatuses' => Pig::STATUSES,
            'sexOptions' => Pig::SEX_OPTIONS,
        ]);
    }

    public function store(StorePigRequest $request, PigCycle $cycle): RedirectResponse
    {
        if ($cycle->isArchived()) {
            return back()->withErrors([
                'cycle' => 'Archived cycles cannot accept new pig profiles.',
            ]);
        }

        /** @var array{pig: Pig, adjustment: PigCycleAdjustment|null} $result */
        $result = DB::transaction(function () use ($request, $cycle): array {
            $pig = $cycle->pigs()->create([
                ...$request->validated(),
                'created_by' => $request->user()->id,
            ]);

            if (! $cycle->has_pig_profiles) {
                $cycle->update([
                    'has_pig_profiles' => true,
                    'last_reviewed_at' => now(),
                ]);
            }

            $adjustment = $this->syncCycleCountForPigStatusTransition(
                $cycle,
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
                'cycle_count_adjusted',
                "Auto-adjusted {$cycle->batch_code} from {$adjustment->quantity_before} to {$adjustment->quantity_after} via pig #{$pig->pig_no} status Counted -> {$pig->status}."
            );
        }

        $this->recordAudit(
            $request,
            'pig_profile_created',
            "Created pig profile #{$pig->pig_no} in cycle {$cycle->batch_code}."
        );

        return redirect()
            ->route('cycles.show', $cycle)
            ->with('status', 'Pig profile added successfully.');
    }

    public function update(UpdatePigRequest $request, PigCycle $cycle, Pig $pig): RedirectResponse
    {
        if ($pig->batch_id !== $cycle->id) {
            abort(404);
        }

        if ($cycle->isArchived()) {
            return back()->withErrors([
                'cycle' => 'Archived cycles cannot modify pig profiles.',
            ]);
        }

        $previousStatus = (string) $pig->status;

        /** @var array{pig: Pig, adjustment: PigCycleAdjustment|null} $result */
        $result = DB::transaction(function () use ($request, $cycle, $pig, $previousStatus): array {
            $pig->update($request->validated());

            $adjustment = $this->syncCycleCountForPigStatusTransition(
                $cycle,
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
                'cycle_count_adjusted',
                "Auto-adjusted {$cycle->batch_code} from {$adjustment->quantity_before} to {$adjustment->quantity_after} via pig #{$pig->pig_no} status {$previousStatus} -> {$pig->status}."
            );
        }

        $this->recordAudit(
            $request,
            'pig_profile_updated',
            "Updated pig profile #{$pig->pig_no} in cycle {$cycle->batch_code}."
        );

        return redirect()
            ->route('cycles.show', $cycle)
            ->with('status', 'Pig profile updated successfully.');
    }

    public function destroy(Request $request, PigCycle $cycle, Pig $pig): RedirectResponse
    {
        if ($pig->batch_id !== $cycle->id) {
            abort(404);
        }

        if ($cycle->isArchived()) {
            return back()->withErrors([
                'cycle' => 'Archived cycles cannot modify pig profiles.',
            ]);
        }

        $pigNo = (int) $pig->pig_no;
        $statusBeforeDelete = (string) $pig->status;

        /** @var array{adjustment: PigCycleAdjustment|null} $result */
        $result = DB::transaction(function () use ($request, $cycle, $pig): array {
            $adjustment = null;

            if (Pig::statusCountsTowardBatch((string) $pig->status)) {
                $adjustment = $this->syncCycleCountForPigStatusTransition(
                    $cycle,
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
                'cycle_count_adjusted',
                "Auto-adjusted {$cycle->batch_code} from {$adjustment->quantity_before} to {$adjustment->quantity_after} via pig #{$pigNo} deletion."
            );
        }

        $this->recordAudit(
            $request,
            'pig_profile_deleted',
            "Deleted pig profile #{$pigNo} ({$statusBeforeDelete}) in cycle {$cycle->batch_code}."
        );

        return redirect()
            ->route('cycles.profiles.index', $cycle)
            ->with('status', 'Pig profile deleted successfully.');
    }

    private function syncCycleCountForPigStatusTransition(
        PigCycle $cycle,
        ?string $previousStatus,
        string $newStatus,
        int $actorId,
        int $pigNo
    ): ?PigCycleAdjustment {
        $wasCounted = Pig::statusCountsTowardBatch($previousStatus);
        $isCounted = Pig::statusCountsTowardBatch($newStatus);

        if ($wasCounted === $isCounted) {
            return null;
        }

        $delta = $isCounted ? 1 : -1;

        $lockedCycle = PigCycle::query()
            ->whereKey($cycle->id)
            ->lockForUpdate()
            ->firstOrFail();

        $before = (int) $lockedCycle->current_count;
        $after = $before + $delta;

        if ($after < 0) {
            throw ValidationException::withMessages([
                'status' => 'Status change cannot reduce cycle count below zero.',
            ]);
        }

        $lockedCycle->update([
            'current_count' => $after,
            'last_reviewed_at' => now(),
        ]);

        return PigCycleAdjustment::create([
            'batch_id' => $lockedCycle->id,
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
