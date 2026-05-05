<?php

namespace App\Services\PigRegistry\Reports;

use App\Models\Pig;
use App\Models\PigCycle;
use Illuminate\Database\Eloquent\Builder;

class InventoryReportService
{
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
            $cycleQuery->whereBetween('date_of_purchase', [$filters['start_date'], $filters['end_date']]);
        }

        $cycles = $cycleQuery->withCount([
            'pigs as active_pigs_count' => fn (Builder $query) => $query->whereNotIn('status', Pig::OUT_OF_COUNT_STATUSES),
            'pigs as deceased_pigs_count' => fn (Builder $query) => $query->where('status', 'Deceased'),
            'pigs as sold_pigs_count' => fn (Builder $query) => $query->where('status', 'Sold'),
        ])->with(['caretaker:id,name'])->get();

        $rows = $cycles->map(fn (PigCycle $cycle): array => [
            'cycle_code' => $cycle->batch_code,
            'stage' => $cycle->stage,
            'status' => $cycle->status,
            'caretaker' => $cycle->caretaker?->name,
            'initial_count' => (int) $cycle->initial_count,
            'current_count' => (int) $cycle->current_count,
            'active_pigs' => (int) $cycle->active_pigs_count,
            'sold_pigs' => (int) $cycle->sold_pigs_count,
            'deceased_pigs' => (int) $cycle->deceased_pigs_count,
        ])->values()->all();

        $summary = [
            'cycle_count' => $cycles->count(),
            'total_initial' => (int) $cycles->sum('initial_count'),
            'total_current' => (int) $cycles->sum('current_count'),
            'total_active' => (int) $cycles->sum('active_pigs_count'),
            'total_sold' => (int) $cycles->sum('sold_pigs_count'),
            'total_deceased' => (int) $cycles->sum('deceased_pigs_count'),
        ];

        $byStage = $cycles->groupBy('stage')->map(fn ($group) => (int) $group->sum('current_count'));

        return [
            'summary' => $summary,
            'rows' => $rows,
            'charts' => [
                'inventoryByStage' => [
                    'labels' => $byStage->keys()->values()->all(),
                    'datasets' => [[
                        'label' => 'Pig Count',
                        'data' => $byStage->values()->all(),
                        'backgroundColor' => ['#0c6d57', '#1e8a6d', '#3ca88d', '#5dc6a2', '#8edcbc', '#0a5a48'],
                    ]],
                ],
            ],
            'empty' => $cycles->isEmpty(),
        ];
    }
}
