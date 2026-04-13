<?php

namespace App\Http\Controllers\President;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\PigRegistry\StoreCycleHealthIncidentRequest;
use App\Models\PigCycle;
use App\Services\PigRegistry\RecordCycleHealthIncidentService;
use Illuminate\Http\RedirectResponse;

class PresidentCycleHealthIncidentController extends Controller
{
    use RecordsAuditTrail;

    public function store(
        StoreCycleHealthIncidentRequest $request,
        PigCycle $cycle,
        RecordCycleHealthIncidentService $recordCycleHealthIncidentService
    ): RedirectResponse {
        if ($cycle->isArchived()) {
            return back()->withErrors([
                'cycle' => 'Archived cycles cannot accept new health incidents without reopening.',
            ]);
        }

        $incident = $recordCycleHealthIncidentService->handle($cycle, $request->validated(), $request->user());

        $this->recordAudit(
            $request,
            'cycle_health_incident_recorded',
            "Recorded {$incident->incident_type} incident for cycle {$cycle->batch_code} affecting {$incident->affected_count} pig(s).",
            'health_monitoring'
        );

        return redirect()
            ->route('health.cycles.show', $cycle)
            ->with('status', 'Health incident recorded successfully.');
    }
}
