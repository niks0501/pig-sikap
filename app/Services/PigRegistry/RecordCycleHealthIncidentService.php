<?php

namespace App\Services\PigRegistry;

use App\Models\CycleHealthIncident;
use App\Models\PigCycle;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecordCycleHealthIncidentService
{
    public function __construct(
        private readonly AdjustPigCycleCountService $adjustPigCycleCountService
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(PigCycle $cycle, array $payload, User $actor): CycleHealthIncident
    {
        return DB::transaction(function () use ($cycle, $payload, $actor): CycleHealthIncident {
            $incident = CycleHealthIncident::query()->create([
                'batch_id' => $cycle->id,
                'incident_type' => (string) $payload['incident_type'],
                'date_reported' => (string) $payload['date_reported'],
                'affected_count' => (int) $payload['affected_count'],
                'suspected_cause' => $payload['suspected_cause'] ?? null,
                'treatment_given' => $payload['treatment_given'] ?? null,
                'remarks' => $payload['remarks'] ?? null,
                'media_path' => $payload['media_path'] ?? null,
                'reported_by' => $actor->id,
            ]);

            if ($incident->incident_type === 'deceased' && $incident->affected_count > 0) {
                $this->adjustPigCycleCountService->handle($cycle, [
                    'adjustment_type' => 'decrease',
                    'quantity_change' => (int) $incident->affected_count,
                    'reason' => 'mortality',
                    'remarks' => 'Auto-adjusted from health incident #'.$incident->id,
                ], $actor);
            }

            return $incident;
        });
    }
}
