<?php

namespace App\Http\Controllers\President;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\PigRegistry\StorePigCycleAdjustmentRequest;
use App\Models\PigCycle;
use App\Services\PigRegistry\AdjustPigCycleCountService;
use Illuminate\Http\RedirectResponse;

class PresidentPigCycleAdjustmentController extends Controller
{
    use RecordsAuditTrail;

    public function store(
        StorePigCycleAdjustmentRequest $request,
        PigCycle $cycle,
        AdjustPigCycleCountService $adjustPigCycleCountService
    ): RedirectResponse {
        if ($cycle->isArchived()) {
            return back()->withErrors([
                'cycle' => 'Archived cycles cannot be adjusted without reopening.',
            ]);
        }

        $adjustment = $adjustPigCycleCountService->handle($cycle, [
            ...$request->validated(),
            'source_module' => 'pig_registry',
            'source_type' => 'manual_adjustment',
        ], $request->user());

        $this->recordAudit(
            $request,
            'cycle_count_adjusted',
            "Adjusted {$cycle->batch_code} from {$adjustment->quantity_before} to {$adjustment->quantity_after} ({$adjustment->reason}).",
            'pig_registry',
            [
                'cycle_id' => $cycle->id,
                'adjustment_id' => $adjustment->id,
                'source_event_key' => $adjustment->source_event_key,
            ]
        );

        return redirect()
            ->route('cycles.show', $cycle)
            ->with('status', 'Cycle count adjusted successfully.');
    }
}
