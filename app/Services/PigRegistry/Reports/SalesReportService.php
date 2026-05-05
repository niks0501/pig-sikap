<?php

namespace App\Services\PigRegistry\Reports;

use App\Models\PigCycleSale;
use App\Services\PigRegistry\SalesSummaryService;

class SalesReportService
{
    public function __construct(private readonly SalesSummaryService $summaryService)
    {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function generate(array $filters): array
    {
        $query = PigCycleSale::query()->with(['cycle:id,batch_code', 'buyer:id,name']);

        if (! empty($filters['cycle_id'])) {
            $query->where('batch_id', $filters['cycle_id']);
        }

        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('sale_date', [$filters['start_date'], $filters['end_date']]);
        }

        $rows = $query->orderByDesc('sale_date')->get();
        $summary = $this->summaryService->buildFromQuery($query);

        $salesTotal = round((float) $rows->sum('amount'), 2);
        $expensesTotal = round((float) \App\Models\PigCycleExpense::query()
            ->when(! empty($filters['cycle_id']), fn ($q) => $q->where('batch_id', $filters['cycle_id']))
            ->when(! empty($filters['start_date']) && ! empty($filters['end_date']), fn ($q) => $q->whereBetween('expense_date', [$filters['start_date'], $filters['end_date']]))
            ->sum('amount'), 2);

        return [
            'summary' => $summary,
            'rows' => $rows->map(fn (PigCycleSale $sale): array => [
                'sale_date' => $sale->sale_date?->format('M d, Y'),
                'cycle_code' => $sale->cycle?->batch_code,
                'buyer' => $sale->buyer?->name,
                'pigs_sold' => (int) $sale->pigs_sold,
                'amount' => (float) $sale->amount,
                'amount_paid' => (float) $sale->amount_paid,
                'payment_status' => $sale->payment_status,
            ])->values()->all(),
            'charts' => [
                'salesVsExpenses' => [
                    'labels' => ['Sales', 'Expenses'],
                    'datasets' => [[
                        'data' => [$salesTotal, $expensesTotal],
                        'backgroundColor' => ['#0c6d57', '#f87171'],
                    ]],
                ],
            ],
            'empty' => $rows->isEmpty(),
        ];
    }
}
