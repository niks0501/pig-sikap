<?php

namespace App\Services\PigRegistry;

use App\Models\CycleHealthIncident;
use App\Models\CycleHealthTask;
use App\Models\Pig;
use App\Models\PigCycle;
use Illuminate\Support\Facades\DB;

class CycleSummaryService
{
    /**
     * @return array<string, int>
     */
    public function forDashboard(): array
    {
        $terminalStatuses = CycleHealthTask::TERMINAL_STATUSES;

        return [
            'active_cycles' => PigCycle::query()->activeRecords()->count(),
            'total_piglets' => (int) PigCycle::query()->where('stage', 'Piglet')->sum('current_count'),
            'total_fatteners' => (int) PigCycle::query()->where('stage', 'Fattening')->sum('current_count'),
            'total_sick' => (int) CycleHealthIncident::query()
                ->whereIn('incident_type', ['sick', 'isolated'])
                ->sum('affected_count'),
            'total_deceased' => (int) CycleHealthIncident::query()
                ->where('incident_type', 'deceased')
                ->sum('affected_count'),
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
            'total_health_incidents' => CycleHealthIncident::query()->count(),
            'total_health_mortality' => (int) CycleHealthIncident::query()
                ->where('incident_type', 'deceased')
                ->sum('affected_count'),
            'total_health_completed_recently' => CycleHealthTask::query()
                ->where('status', 'completed')
                ->whereDate('actual_date', '>=', today()->subDays(7))
                ->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function forHealthDashboard(): array
    {
        $terminalStatuses = CycleHealthTask::TERMINAL_STATUSES;

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
            'sick_cases' => (int) CycleHealthIncident::query()
                ->whereIn('incident_type', ['sick', 'isolated'])
                ->sum('affected_count'),
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

        $initialCount = (int) $cycle->initial_count;
        $currentCount = (int) $cycle->current_count;

        $incidentSickCount = (int) $cycle->healthIncidents()
            ->where('incident_type', 'sick')
            ->sum('affected_count');

        $incidentIsolatedCount = (int) $cycle->healthIncidents()
            ->where('incident_type', 'isolated')
            ->sum('affected_count');

        $incidentDeceasedCount = (int) $cycle->healthIncidents()
            ->where('incident_type', 'deceased')
            ->sum('affected_count');

        $sickCount = (bool) $cycle->has_pig_profiles
            ? (int) ($statusCounts['Sick'] ?? 0)
            : $incidentSickCount;

        $isolatedCount = (bool) $cycle->has_pig_profiles
            ? (int) ($statusCounts['Isolated'] ?? 0)
            : $incidentIsolatedCount;

        $soldCount = (bool) $cycle->has_pig_profiles
            ? (int) ($statusCounts['Sold'] ?? 0)
            : 0;

        $deceasedCount = (bool) $cycle->has_pig_profiles
            ? max((int) ($statusCounts['Deceased'] ?? 0), $incidentDeceasedCount)
            : $incidentDeceasedCount;

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
            'remaining_count' => $currentCount,
            'mortality_rate' => $mortalityRate,
        ];
    }
}
