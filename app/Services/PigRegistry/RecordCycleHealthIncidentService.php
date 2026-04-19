<?php

namespace App\Services\PigRegistry;

use App\Models\CycleHealthIncident;
use App\Models\PigCycle;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RecordCycleHealthIncidentService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(PigCycle $cycle, array $payload, User $actor): CycleHealthIncident
    {
        $storedMediaPath = null;

        try {
            return DB::transaction(function () use ($cycle, $payload, $actor, &$storedMediaPath): CycleHealthIncident {
                $eventKey = $this->normalizeString($payload['event_key'] ?? null);
                $incidentType = CycleHealthIncident::normalizeIncidentType($payload['incident_type'] ?? null);
                $resolutionTarget = CycleHealthIncident::normalizeResolutionTarget($payload['resolution_target'] ?? null);
                $resolvedIncidentId = $this->resolveResolvedIncidentId($cycle, $payload, $resolutionTarget);

                if ($eventKey !== null) {
                    $existing = CycleHealthIncident::query()
                        ->where('batch_id', $cycle->id)
                        ->where('event_key', $eventKey)
                        ->first();

                    if ($existing !== null) {
                        return $existing;
                    }
                }

                $uploadedMedia = $payload['media'] ?? null;
                $storedMediaPath = $uploadedMedia instanceof UploadedFile
                    ? $uploadedMedia->store('uploads', 'public')
                    : null;

                $incident = CycleHealthIncident::query()->create([
                    'batch_id' => $cycle->id,
                    'event_key' => $eventKey,
                    'pig_id' => $payload['pig_id'] ?? null,
                    'source_channel' => $this->normalizeString($payload['source_channel'] ?? null) ?? 'health_module',
                    'incident_type' => $incidentType,
                    'date_reported' => (string) $payload['date_reported'],
                    'affected_count' => (int) $payload['affected_count'],
                    'suspected_cause' => $payload['suspected_cause'] ?? null,
                    'treatment_given' => $payload['treatment_given'] ?? null,
                    'remarks' => $payload['remarks'] ?? null,
                    'media_path' => $storedMediaPath ?? ($payload['media_path'] ?? null),
                    'resolution_target' => $resolutionTarget,
                    'resolved_incident_id' => $resolvedIncidentId,
                    'reported_by' => $actor->id,
                ]);

                return $incident;
            });
        } catch (Throwable $exception) {
            if (is_string($storedMediaPath) && $storedMediaPath !== '') {
                Storage::disk('public')->delete($storedMediaPath);
            }

            throw $exception;
        }
    }

    private function normalizeString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveResolvedIncidentId(PigCycle $cycle, array $payload, ?string $resolutionTarget): ?int
    {
        $resolvedIncidentId = $this->normalizePositiveInt($payload['resolved_incident_id'] ?? null);

        if ($resolvedIncidentId === null) {
            return null;
        }

        $resolvedIncident = CycleHealthIncident::query()
            ->whereKey($resolvedIncidentId)
            ->where('batch_id', $cycle->id)
            ->first();

        if ($resolvedIncident === null) {
            return null;
        }

        if ($resolutionTarget === null) {
            return $resolvedIncident->id;
        }

        $resolvedIncidentType = CycleHealthIncident::normalizeIncidentType((string) $resolvedIncident->incident_type);

        return $resolvedIncidentType === $resolutionTarget
            ? $resolvedIncident->id
            : null;
    }

    private function normalizePositiveInt(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $integerValue = (int) $value;

        return $integerValue > 0 ? $integerValue : null;
    }
}
