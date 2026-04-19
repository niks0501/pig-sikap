<?php

namespace App\Services\PigRegistry;

use App\Models\CycleHealthIncident;
use App\Models\PigCycle;
use Illuminate\Support\Collection;

class CycleHealthStateProjector
{
    /**
     * @return array<string, mixed>
     */
    public function projectForCycle(PigCycle $cycle): array
    {
        $incidents = $cycle->healthIncidents()
            ->select([
                'id',
                'batch_id',
                'incident_type',
                'affected_count',
                'resolution_target',
                'resolved_incident_id',
                'date_reported',
            ])
            ->orderBy('date_reported')
            ->orderBy('id')
            ->get();

        return $this->projectIncidents($incidents, (int) $cycle->current_count);
    }

    /**
     * @param  Collection<int, CycleHealthIncident>  $incidents
     * @return array<string, mixed>
     */
    public function projectIncidents(Collection $incidents, int $cycleCurrentCount = 0): array
    {
        $state = [
            'active_sick' => 0,
            'active_isolated' => 0,
            'lifetime_sick' => 0,
            'lifetime_isolated' => 0,
            'lifetime_recovered' => 0,
            'lifetime_deceased' => 0,
        ];

        $orderedIncidents = $incidents
            ->sortBy([
                ['date_reported', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        $orderedIncidents->each(function (CycleHealthIncident $incident) use (&$state): void {
            $incidentType = CycleHealthIncident::normalizeIncidentType((string) $incident->incident_type);
            $affectedCount = max((int) $incident->affected_count, 0);
            $resolutionTarget = CycleHealthIncident::normalizeResolutionTarget($incident->resolution_target);

            if ($affectedCount < 1) {
                return;
            }

            if ($incidentType === CycleHealthIncident::INCIDENT_TYPE_SICK) {
                $state['active_sick'] += $affectedCount;
                $state['lifetime_sick'] += $affectedCount;

                return;
            }

            if ($incidentType === CycleHealthIncident::INCIDENT_TYPE_ISOLATED) {
                $state['active_isolated'] += $affectedCount;
                $state['lifetime_isolated'] += $affectedCount;

                return;
            }

            if ($incidentType === CycleHealthIncident::INCIDENT_TYPE_RECOVERED) {
                $state['lifetime_recovered'] += $affectedCount;
                $this->applyResolution($state, $resolutionTarget, $affectedCount, ['sick', 'isolated']);

                return;
            }

            if ($incidentType === CycleHealthIncident::INCIDENT_TYPE_DECEASED) {
                $state['lifetime_deceased'] += $affectedCount;

                if ($resolutionTarget !== null) {
                    $this->applyResolution($state, $resolutionTarget, $affectedCount, ['isolated', 'sick']);
                }
            }
        });

        $currentlyAffected = $state['active_sick'] + $state['active_isolated'];

        return [
            'active' => [
                'currently_sick' => $state['active_sick'],
                'currently_isolated' => $state['active_isolated'],
                'currently_affected' => $currentlyAffected,
                'healthy_now' => max($cycleCurrentCount - $currentlyAffected, 0),
            ],
            'lifetime' => [
                'total_sick_reported' => $state['lifetime_sick'],
                'total_isolated_reported' => $state['lifetime_isolated'],
                'total_recovered_reported' => $state['lifetime_recovered'],
                'total_deceased_reported' => $state['lifetime_deceased'],
            ],
            'unresolved' => [
                'sick' => $state['active_sick'],
                'isolated' => $state['active_isolated'],
                'total' => $currentlyAffected,
            ],
            'incident_total' => $orderedIncidents->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function unresolvedCountsForCycle(PigCycle $cycle): array
    {
        $projected = $this->projectForCycle($cycle);

        return [
            'sick' => (int) ($projected['unresolved']['sick'] ?? 0),
            'isolated' => (int) ($projected['unresolved']['isolated'] ?? 0),
            'total' => (int) ($projected['unresolved']['total'] ?? 0),
        ];
    }

    /**
     * @param  array<string, int>  $state
     * @param  list<string>  $fallbackOrder
     */
    private function applyResolution(array &$state, ?string $resolutionTarget, int $affectedCount, array $fallbackOrder): void
    {
        if ($affectedCount < 1) {
            return;
        }

        if ($resolutionTarget !== null) {
            $this->reduceBucket($state, $resolutionTarget, $affectedCount);

            return;
        }

        $remaining = $affectedCount;

        foreach ($fallbackOrder as $target) {
            if ($remaining < 1) {
                break;
            }

            $resolved = $this->reduceBucket($state, $target, $remaining);
            $remaining -= $resolved;
        }
    }

    /**
     * @param  array<string, int>  $state
     */
    private function reduceBucket(array &$state, string $target, int $affectedCount): int
    {
        if ($affectedCount < 1) {
            return 0;
        }

        $stateKey = match ($target) {
            'sick' => 'active_sick',
            'isolated' => 'active_isolated',
            default => null,
        };

        if ($stateKey === null) {
            return 0;
        }

        $current = max((int) ($state[$stateKey] ?? 0), 0);
        $resolved = min($current, $affectedCount);

        $state[$stateKey] = $current - $resolved;

        return $resolved;
    }
}
