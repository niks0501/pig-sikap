<?php

namespace App\Services\PigRegistry;

use App\Models\CycleHealthIncident;
use App\Models\PigCycle;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecordHealthIncidentWithOperationalImpactService
{
    public function __construct(
        private readonly RecordCycleHealthIncidentService $recordCycleHealthIncidentService,
        private readonly CycleInventoryImpactService $cycleInventoryImpactService
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(PigCycle $cycle, array $payload, User $actor): CycleHealthIncident
    {
        return DB::transaction(function () use ($cycle, $payload, $actor): CycleHealthIncident {
            $incident = $this->recordCycleHealthIncidentService->handle($cycle, $payload, $actor);

            if (
                CycleHealthIncident::normalizeIncidentType((string) $incident->incident_type) === CycleHealthIncident::INCIDENT_TYPE_DECEASED
                && $incident->affected_count > 0
            ) {
                $this->cycleInventoryImpactService->applyDelta(
                    $cycle,
                    -((int) $incident->affected_count),
                    'mortality',
                    $actor,
                    [
                        'adjustment_type' => 'decrease',
                        'remarks' => 'Auto-adjusted from health incident #'.$incident->id,
                        'source_module' => 'health_monitoring',
                        'source_type' => 'cycle_health_incident',
                        'source_id' => $incident->id,
                        'source_event_key' => $incident->event_key,
                    ]
                );
            }

            return $incident;
        });
    }
}
