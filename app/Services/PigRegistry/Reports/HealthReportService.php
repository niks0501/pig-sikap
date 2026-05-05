<?php

namespace App\Services\PigRegistry\Reports;

use App\Models\CycleHealthIncident;
use App\Models\PigCycle;
use App\Services\PigRegistry\CycleHealthSummaryService;

class HealthReportService
{
    public function __construct(private readonly CycleHealthSummaryService $summaryService)
    {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function generate(array $filters): array
    {
        $cycleQuery = PigCycle::query();

        if (! empty($filters['cycle_id'])) {
            $cycleQuery->where('id', $filters['cycle_id']);
        }

        $cycles = $cycleQuery->get();
        $rows = [];

        foreach ($cycles as $cycle) {
            $summary = $this->summaryService->handle($cycle);
            $counts = $summary['counts'] ?? [];

            $rows[] = [
                'cycle_code' => $cycle->batch_code,
                'due_today' => (int) ($counts['due_today'] ?? 0),
                'overdue' => (int) ($counts['overdue'] ?? 0),
                'completed_recently' => (int) ($counts['completed_recently'] ?? 0),
                'currently_affected' => (int) ($counts['currently_affected'] ?? 0),
                'total_incidents' => (int) ($counts['incidents'] ?? 0),
                'mortality' => (int) ($counts['mortality'] ?? 0),
            ];
        }

        $summaryTotals = [
            'cycle_count' => $cycles->count(),
            'total_due_today' => collect($rows)->sum('due_today'),
            'total_overdue' => collect($rows)->sum('overdue'),
            'total_currently_affected' => collect($rows)->sum('currently_affected'),
            'total_mortality' => collect($rows)->sum('mortality'),
        ];

        return [
            'summary' => $summaryTotals,
            'rows' => $rows,
            'empty' => $cycles->isEmpty(),
        ];
    }
}
