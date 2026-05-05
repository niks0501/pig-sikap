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

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $cycleQuery->whereHas('healthIncidents', fn ($q) =>
                $q->whereBetween('date_reported', [$filters['start_date'], $filters['end_date']])
            );
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
            'charts' => [
                'healthStatus' => [
                    'labels' => ['Due Today', 'Overdue', 'Completed', 'Affected', 'Mortality'],
                    'datasets' => [[
                        'data' => [
                            (int) $summaryTotals['total_due_today'],
                            (int) $summaryTotals['total_overdue'],
                            (int) collect($rows)->sum('completed_recently'),
                            (int) $summaryTotals['total_currently_affected'],
                            (int) $summaryTotals['total_mortality'],
                        ],
                        'backgroundColor' => ['#3b82f6', '#f59e0b', '#10b981', '#ef4444', '#6b7280'],
                    ]],
                ],
            ],
            'empty' => $cycles->isEmpty(),
        ];
    }
}
