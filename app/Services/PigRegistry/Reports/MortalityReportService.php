<?php

namespace App\Services\PigRegistry\Reports;

use App\Models\CycleHealthIncident;
use App\Models\PigCycle;

class MortalityReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function generate(array $filters): array
    {
        $query = CycleHealthIncident::query()
            ->where('incident_type', CycleHealthIncident::INCIDENT_TYPE_DECEASED)
            ->with(['cycle:id,batch_code', 'reportedBy:id,name']);

        if (! empty($filters['cycle_id'])) {
            $query->where('batch_id', $filters['cycle_id']);
        }

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('date_reported', [$filters['start_date'], $filters['end_date']]);
        }

        $incidents = $query->orderBy('date_reported', 'desc')->get();

        $rows = $incidents->map(fn (CycleHealthIncident $incident): array => [
            'date_reported' => $incident->date_reported?->format('M d, Y'),
            'cycle_code' => $incident->cycle?->batch_code,
            'affected_count' => (int) $incident->affected_count,
            'suspected_cause' => $incident->suspected_cause,
            'reported_by' => $incident->reportedBy?->name,
            'media_path' => $incident->media_path,
        ])->values()->all();

        $byCause = $incidents->groupBy('suspected_cause')->map(fn ($items) => (int) $items->sum('affected_count'));

        return [
            'summary' => [
                'total_cases' => $incidents->count(),
                'total_deceased' => (int) $incidents->sum('affected_count'),
            ],
            'rows' => $rows,
            'charts' => [
                'mortalityByCause' => [
                    'labels' => $byCause->keys()->map(fn ($c) => $c ?: 'Unknown')->values()->all(),
                    'datasets' => [[
                        'data' => $byCause->values()->all(),
                        'backgroundColor' => ['#dc2626', '#f87171', '#fca5a5', '#fecaca', '#ef4444'],
                    ]],
                ],
            ],
            'empty' => $incidents->isEmpty(),
        ];
    }
}
