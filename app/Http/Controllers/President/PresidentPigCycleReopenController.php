<?php

namespace App\Http\Controllers\President;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\PigRegistry\ReopenPigCycleRequest;
use App\Models\PigCycle;
use App\Services\PigRegistry\UpdatePigCycleStatusService;
use Illuminate\Http\RedirectResponse;

class PresidentPigCycleReopenController extends Controller
{
    use RecordsAuditTrail;

    public function store(
        ReopenPigCycleRequest $request,
        PigCycle $cycle,
        UpdatePigCycleStatusService $updatePigCycleStatusService
    ): RedirectResponse {
        $history = $updatePigCycleStatusService->handle(
            $cycle,
            $request->validated(),
            $request->user(),
            [
                'allow_archived_transition' => true,
                'transition_origin' => 'cycle_reopen_endpoint',
                'transition_type' => 'reopen',
            ]
        );

        $this->recordAudit(
            $request,
            'cycle_reopened',
            "Reopened cycle {$cycle->batch_code} to {$history->new_stage} / {$history->new_status}.",
            'pig_registry',
            [
                'cycle_id' => $cycle->id,
                'history_id' => $history->id,
            ]
        );

        return redirect()
            ->route('cycles.show', $cycle)
            ->with('status', 'Cycle reopened successfully.');
    }
}
