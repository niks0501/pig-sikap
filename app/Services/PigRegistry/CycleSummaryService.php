<?php

namespace App\Services\PigRegistry;

use App\Models\CycleHealthIncident;
use App\Models\CycleHealthTask;
use App\Models\PigCycle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CycleSummaryService
{
    public function __construct(
        private readonly CycleHealthStateProjector $cycleHealthStateProjector
    ) {}

    /**
     * @return array<string, int>
     */
    public function forDashboard(): array
    {
        $terminalStatuses = CycleHealthTask::TERMINAL_STATUSES;
        $activeCycles = PigCycle::query()->activeRecords()->get(['id', 'current_count']);
        $aggregatedHealth = $this->aggregateHealthTotals($activeCycles);

        return [
            'active_cycles' => $activeCycles->count(),
            'total_piglets' => (int) PigCycle::query()->where('stage', 'Piglet')->sum('current_count'),
            'total_fatteners' => (int) PigCycle::query()->where('stage', 'Fattening')->sum('current_count'),
            'total_sick' => (int) $aggregatedHealth['total_currently_affected'],
            'total_deceased' => (int) $aggregatedHealth['total_deceased_reported'],
            'ready_for_sale_cycles' => PigCycle::query()->where('status', 'Ready for Sale')->count(),
            'total_health_due_today' => CycleHealthTask::query()
                ->whereDate('planned_start_date', today())
                ->whereNotIn('status', $terminalStatuses)
                ->count(),
            'total_health_overdue' => CycleHealthTask::query()
                ->whereDate('planned_start_date', '<', today())
                ->whereNotIn('status', $terminalStatuses)
                ->count(),
            'total_health_active_oral_periods' => CycleHealthTask::query()
                ->where('task_type', 'oral_medication_period')
                ->whereDate('planned_start_date', '<=', today())
                ->where(function ($query): void {
                    $query->whereNull('planned_end_date')
                        ->orWhereDate('planned_end_date', '>=', today());
                })
                ->whereNotIn('status', ['skipped', 'not_applicable'])
                ->count(),
            'total_health_incidents' => (int) $aggregatedHealth['total_incidents'],
            'total_health_mortality' => (int) $aggregatedHealth['total_deceased_reported'],
            'total_health_completed_recently' => CycleHealthTask::query()
                ->where('status', 'completed')
                ->whereDate('actual_date', '>=', today()->subDays(7))
                ->count(),
            'total_currently_sick' => (int) $aggregatedHealth['total_currently_sick'],
            'total_currently_isolated' => (int) $aggregatedHealth['total_currently_isolated'],
            'total_currently_affected' => (int) $aggregatedHealth['total_currently_affected'],
            'total_health_healthy_now' => (int) $aggregatedHealth['total_healthy_now'],
            'total_health_sick_reported' => (int) $aggregatedHealth['total_sick_reported'],
            'total_health_isolated_reported' => (int) $aggregatedHealth['total_isolated_reported'],
            'total_health_recovered_reported' => (int) $aggregatedHealth['total_recovered_reported'],
            'total_health_deceased_reported' => (int) $aggregatedHealth['total_deceased_reported'],
        ];
    }

    /**
     * @return array<string, int>
     */
    public function forHealthDashboard(): array
    {
        $terminalStatuses = CycleHealthTask::TERMINAL_STATUSES;
        $activeCycles = PigCycle::query()->activeRecords()->get(['id', 'current_count']);
        $aggregatedHealth = $this->aggregateHealthTotals($activeCycles);

        return [
            'upcoming' => CycleHealthTask::query()
                ->whereDate('planned_start_date', '>', today())
                ->whereNotIn('status', $terminalStatuses)
                ->count(),
            'overdue' => CycleHealthTask::query()
                ->whereDate('planned_start_date', '<', today())
                ->whereNotIn('status', $terminalStatuses)
                ->count(),
            'completed' => CycleHealthTask::query()->where('status', 'completed')->count(),
            'sick_cases' => (int) $aggregatedHealth['total_currently_affected'],
            'currently_sick' => (int) $aggregatedHealth['total_currently_sick'],
            'currently_isolated' => (int) $aggregatedHealth['total_currently_isolated'],
            'currently_affected' => (int) $aggregatedHealth['total_currently_affected'],
            'total_recovered_reported' => (int) $aggregatedHealth['total_recovered_reported'],
            'total_deceased_reported' => (int) $aggregatedHealth['total_deceased_reported'],
        ];
    }

    /**
     * @return array<string, int|float>
     */
    public function forCycle(PigCycle $cycle): array
    {
        $statusCounts = $cycle->pigs()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');
        $projectedHealth = $this->cycleHealthStateProjector->projectForCycle($cycle);
        $activeMetrics = $projectedHealth['active'] ?? [];
        $lifetimeMetrics = $projectedHealth['lifetime'] ?? [];

        $initialCount = (int) $cycle->initial_count;
        $currentCount = (int) $cycle->current_count;

        $sickCount = (int) ($activeMetrics['currently_sick'] ?? 0);
        $isolatedCount = (int) ($activeMetrics['currently_isolated'] ?? 0);

        $soldCount = (bool) $cycle->has_pig_profiles
            ? (int) ($statusCounts['Sold'] ?? 0)
            : 0;

        $deceasedCount = (int) ($lifetimeMetrics['total_deceased_reported'] ?? 0);
        $currentlyAffectedCount = (int) ($activeMetrics['currently_affected'] ?? 0);
        $healthyNow = max($currentCount - $currentlyAffectedCount, 0);

        $mortalityRate = $initialCount > 0
            ? round(($deceasedCount / $initialCount) * 100, 2)
            : 0.0;

        return [
            'initial_acquired_count' => $initialCount,
            'current_active_count' => $currentCount,
            'sick_count' => $sickCount,
            'deceased_count' => $deceasedCount,
            'sold_count' => $soldCount,
            'isolated_count' => $isolatedCount,
            'currently_sick' => $sickCount,
            'currently_isolated' => $isolatedCount,
            'currently_affected' => $currentlyAffectedCount,
            'healthy_now' => $healthyNow,
            'total_sick_reported' => (int) ($lifetimeMetrics['total_sick_reported'] ?? 0),
            'total_isolated_reported' => (int) ($lifetimeMetrics['total_isolated_reported'] ?? 0),
            'total_recovered_reported' => (int) ($lifetimeMetrics['total_recovered_reported'] ?? 0),
            'total_deceased_reported' => $deceasedCount,
            'remaining_count' => $currentCount,
            'mortality_rate' => $mortalityRate,
        ];
    }

    /**
     * @param  Collection<int, PigCycle>  $cycles
     * @return array<string, int>
     */
    private function aggregateHealthTotals(Collection $cycles): array
    {
        $cycleIds = $cycles->pluck('id')->map(fn (mixed $id): int => (int) $id)->values();

        if ($cycleIds->isEmpty()) {
            return [
                'total_incidents' => 0,
                'total_currently_sick' => 0,
                'total_currently_isolated' => 0,
                'total_currently_affected' => 0,
                'total_healthy_now' => 0,
                'total_sick_reported' => 0,
                'total_isolated_reported' => 0,
                'total_recovered_reported' => 0,
                'total_deceased_reported' => 0,
            ];
        }

        $incidentsByCycle = CycleHealthIncident::query()
            ->whereIn('batch_id', $cycleIds->all())
            ->select([
                'id',
                'batch_id',
                'incident_type',
                'affected_count',
                'resolution_target',
                'resolved_incident_id',
                'date_reported',
            ])
            ->orderBy('batch_id')
            ->orderBy('date_reported')
            ->orderBy('id')
            ->get()
            ->groupBy('batch_id');

        $totals = [
            'total_incidents' => 0,
            'total_currently_sick' => 0,
            'total_currently_isolated' => 0,
            'total_currently_affected' => 0,
            'total_healthy_now' => 0,
            'total_sick_reported' => 0,
            'total_isolated_reported' => 0,
            'total_recovered_reported' => 0,
            'total_deceased_reported' => 0,
        ];

        $cycles->each(function (PigCycle $cycle) use (&$totals, $incidentsByCycle): void {
            $projected = $this->cycleHealthStateProjector->projectIncidents(
                $incidentsByCycle->get((int) $cycle->id, collect()),
                (int) $cycle->current_count
            );

            $activeMetrics = $projected['active'] ?? [];
            $lifetimeMetrics = $projected['lifetime'] ?? [];

            $totals['total_incidents'] += (int) ($projected['incident_total'] ?? 0);
            $totals['total_currently_sick'] += (int) ($activeMetrics['currently_sick'] ?? 0);
            $totals['total_currently_isolated'] += (int) ($activeMetrics['currently_isolated'] ?? 0);
            $totals['total_currently_affected'] += (int) ($activeMetrics['currently_affected'] ?? 0);
            $totals['total_healthy_now'] += (int) ($activeMetrics['healthy_now'] ?? 0);
            $totals['total_sick_reported'] += (int) ($lifetimeMetrics['total_sick_reported'] ?? 0);
            $totals['total_isolated_reported'] += (int) ($lifetimeMetrics['total_isolated_reported'] ?? 0);
            $totals['total_recovered_reported'] += (int) ($lifetimeMetrics['total_recovered_reported'] ?? 0);
            $totals['total_deceased_reported'] += (int) ($lifetimeMetrics['total_deceased_reported'] ?? 0);
        });

        return $totals;
    }
}
