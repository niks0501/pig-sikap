<?php

namespace App\Http\Controllers\President;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\PigRegistry\StoreCycleHealthIncidentRequest;
use App\Models\PigCycle;
use App\Services\PigRegistry\RecordHealthIncidentWithOperationalImpactService;
use Illuminate\Http\RedirectResponse;

class PresidentCycleHealthIncidentController extends Controller
{
    use RecordsAuditTrail;

    public function store(
        StoreCycleHealthIncidentRequest $request,
        PigCycle $cycle,
        RecordHealthIncidentWithOperationalImpactService $recordHealthIncidentWithOperationalImpactService
    ): RedirectResponse {
        if ($cycle->isArchived()) {
            return back()->withErrors([
                'cycle' => 'Archived cycles are final and cannot accept new health incidents.',
            ]);
        }

        $incident = $recordHealthIncidentWithOperationalImpactService->handle(
            $cycle,
            [
                ...$request->validated(),
                'source_channel' => 'cycle_timeline',
            ],
            $request->user()
        );

        $this->recordAudit(
            $request,
            'cycle_health_incident_recorded',
            "Recorded {$incident->incident_type} incident for cycle {$cycle->batch_code} affecting {$incident->affected_count} pig(s).",
            'health_monitoring',
            [
                'cycle_id' => $cycle->id,
                'cycle_batch_code' => $cycle->batch_code,
                'incident_id' => $incident->id,
                'event_key' => $incident->event_key,
                'incident_type' => $incident->incident_type,
                'affected_count' => (int) $incident->affected_count,
                'resolution_target' => $incident->resolution_target,
                'resolved_incident_id' => $incident->resolved_incident_id,
                'pig_id' => $incident->pig_id,
                'source_channel' => $incident->source_channel,
            ]
        );

        return redirect()
            ->route('health.cycles.show', $cycle)
            ->with('status', 'Health incident recorded successfully.');
    }
}
