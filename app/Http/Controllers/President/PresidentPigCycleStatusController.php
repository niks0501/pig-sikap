<?php

namespace App\Http\Controllers\President;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\PigRegistry\StorePigCycleStatusRequest;
use App\Models\PigCycle;
use App\Services\PigRegistry\UpdatePigCycleStatusService;
use Illuminate\Http\RedirectResponse;

class PresidentPigCycleStatusController extends Controller
{
    use RecordsAuditTrail;

    public function store(
        StorePigCycleStatusRequest $request,
        PigCycle $cycle,
        UpdatePigCycleStatusService $updatePigCycleStatusService
    ): RedirectResponse {
        $history = $updatePigCycleStatusService->handle($cycle, $request->validated(), $request->user());

        $this->recordAudit(
            $request,
            'cycle_status_updated',
            "Updated {$cycle->batch_code} status to {$history->new_stage} / {$history->new_status}."
        );

        return redirect()
            ->route('cycles.show', $cycle)
            ->with('status', 'Cycle status updated successfully.');
    }
}
