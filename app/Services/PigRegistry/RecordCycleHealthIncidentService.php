<?php

namespace App\Services\PigRegistry;

use App\Models\CycleHealthIncident;
use App\Models\PigCycle;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecordCycleHealthIncidentService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(PigCycle $cycle, array $payload, User $actor): CycleHealthIncident
    {
        return DB::transaction(function () use ($cycle, $payload, $actor): CycleHealthIncident {
            $eventKey = $this->normalizeString($payload['event_key'] ?? null);

            if ($eventKey !== null) {
                $existing = CycleHealthIncident::query()
                    ->where('batch_id', $cycle->id)
                    ->where('event_key', $eventKey)
                    ->first();

                if ($existing !== null) {
                    return $existing;
                }
            }

            $incident = CycleHealthIncident::query()->create([
                'batch_id' => $cycle->id,
                'event_key' => $eventKey,
                'pig_id' => $payload['pig_id'] ?? null,
                'source_channel' => $this->normalizeString($payload['source_channel'] ?? null) ?? 'health_module',
                'incident_type' => (string) $payload['incident_type'],
                'date_reported' => (string) $payload['date_reported'],
                'affected_count' => (int) $payload['affected_count'],
                'suspected_cause' => $payload['suspected_cause'] ?? null,
                'treatment_given' => $payload['treatment_given'] ?? null,
                'remarks' => $payload['remarks'] ?? null,
                'media_path' => $payload['media_path'] ?? null,
                'reported_by' => $actor->id,
            ]);

            return $incident;
        });
    }

    private function normalizeString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
