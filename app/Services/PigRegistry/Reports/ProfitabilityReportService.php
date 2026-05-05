<?php

namespace App\Services\PigRegistry\Reports;

use App\Models\PigCycle;
use App\Services\PigRegistry\ComputeCycleProfitabilityService;

class ProfitabilityReportService
{
    public function __construct(private readonly ComputeCycleProfitabilityService $computeService)
    {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function generate(array $filters): array
    {
        $cycleQuery = PigCycle::query()->with(['profitabilitySnapshot', 'caretaker:id,name']);

        if (! empty($filters['cycle_id'])) {
            $cycleQuery->where('id', $filters['cycle_id']);
        }

        $cycles = $cycleQuery->get();
        $rows = [];

        foreach ($cycles as $cycle) {
            $snapshot = $cycle->profitabilitySnapshot;
            $profitability = $snapshot?->toProfitabilitySummary() ?? $this->computeService->compute($cycle);

            $rows[] = [
                'cycle_code' => $cycle->batch_code,
                'status' => $cycle->status,
                'caretaker' => $cycle->caretaker?->name,
                'gross_income' => (float) ($profitability['total_sales'] ?? 0),
                'total_expenses' => (float) ($profitability['total_expenses'] ?? 0),
                'net_profit_or_loss' => (float) ($profitability['net_profit_or_loss'] ?? 0),
                'is_finalized' => (bool) ($profitability['is_finalized'] ?? false),
            ];
        }

        return [
            'summary' => [
                'cycle_count' => $cycles->count(),
                'gross_income' => (float) collect($rows)->sum('gross_income'),
                'total_expenses' => (float) collect($rows)->sum('total_expenses'),
                'net_profit_or_loss' => (float) collect($rows)->sum('net_profit_or_loss'),
            ],
            'rows' => $rows,
            'charts' => [
                'profitabilityPerCycle' => [
                    'labels' => collect($rows)->pluck('cycle_code')->all(),
                    'datasets' => [
                        [
                            'label' => 'Gross Income',
                            'data' => collect($rows)->pluck('gross_income')->all(),
                            'backgroundColor' => '#0c6d57',
                        ],
                        [
                            'label' => 'Net',
                            'data' => collect($rows)->pluck('net_profit_or_loss')->all(),
                            'backgroundColor' => '#8edcbc',
                        ],
                    ],
                ],
            ],
            'empty' => $cycles->isEmpty(),
        ];
    }
}
