<?php

namespace App\Services\PigRegistry\Reports;

use App\Models\PigCycleExpense;
use App\Models\PigCycleSale;
use Illuminate\Support\Carbon;

class MonthlyReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function generate(array $filters): array
    {
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $start = Carbon::parse((string) $filters['start_date']);
            $end = Carbon::parse((string) $filters['end_date']);
        } else {
            $year = (int) ($filters['year'] ?? now()->year);
            $month = (int) ($filters['month'] ?? now()->month);
            $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();
        }

        $expenseQuery = PigCycleExpense::query()->whereBetween('expense_date', [$start, $end]);
        $salesQuery = PigCycleSale::query()->whereBetween('sale_date', [$start, $end]);

        if (! empty($filters['cycle_id'])) {
            $expenseQuery->where('batch_id', $filters['cycle_id']);
            $salesQuery->where('batch_id', $filters['cycle_id']);
        }

        $totalExpenses = round((float) $expenseQuery->sum('amount'), 2);
        $totalSales = round((float) $salesQuery->sum('amount'), 2);
        $totalCollected = round((float) $salesQuery->sum('amount_paid'), 2);
        $net = round($totalSales - $totalExpenses, 2);

        $periodLabel = $start->eq($end) || $start->format('M Y') === $end->format('M Y')
            ? $start->format('F Y')
            : $start->format('M d, Y').' - '.$end->format('M d, Y');

        $perCycleRows = PigCycleSale::query()
            ->selectRaw('batch_id, SUM(amount) as total_sales, SUM(amount_paid) as total_collected, SUM(pigs_sold) as pigs_sold')
            ->whereBetween('sale_date', [$start, $end])
            ->when(! empty($filters['cycle_id']), fn ($q) => $q->where('batch_id', $filters['cycle_id']))
            ->groupBy('batch_id')
            ->with('cycle:id,batch_code')
            ->get()
            ->map(function ($saleRow) use ($start, $end) {
                $cycleExpenses = PigCycleExpense::query()
                    ->where('batch_id', $saleRow->batch_id)
                    ->whereBetween('expense_date', [$start, $end])
                    ->sum('amount');

                return [
                    'cycle_code' => $saleRow->cycle?->batch_code ?? 'Cycle #'.$saleRow->batch_id,
                    'total_sales' => round((float) $saleRow->total_sales, 2),
                    'total_collected' => round((float) $saleRow->total_collected, 2),
                    'total_expenses' => round((float) $cycleExpenses, 2),
                    'net_result' => round((float) ($saleRow->total_sales - $cycleExpenses), 2),
                    'pigs_sold' => (int) $saleRow->pigs_sold,
                ];
            })->values()->all();

        $categoryBreakdown = PigCycleExpense::query()
            ->whereBetween('expense_date', [$start, $end])
            ->when(! empty($filters['cycle_id']), fn ($q) => $q->where('batch_id', $filters['cycle_id']))
            ->get()
            ->groupBy('category')
            ->map(function ($items) use ($totalExpenses) {
                $catTotal = round((float) $items->sum('amount'), 2);
                $pct = $totalExpenses > 0 ? round(($catTotal / $totalExpenses) * 100, 1) : 0;

                return [
                    'category' => \App\Models\PigCycleExpense::categoryLabels()[$items->first()->category] ?? ucfirst((string) $items->first()->category),
                    'amount' => $catTotal,
                    'percent' => $pct,
                ];
            })->values()->all();

        return [
            'summary' => [
                'period' => $periodLabel,
                'total_sales' => $totalSales,
                'total_collected' => $totalCollected,
                'total_expenses' => $totalExpenses,
                'net_result' => $net,
            ],
            'rows' => $perCycleRows,
            'category_breakdown' => $categoryBreakdown,
            'charts' => [
                'monthlyNet' => [
                    'labels' => ['Sales', 'Expenses', 'Net'],
                    'datasets' => [[
                        'data' => [$totalSales, $totalExpenses, $net],
                        'backgroundColor' => ['#0c6d57', '#f87171', $net >= 0 ? '#8edcbc' : '#dc2626'],
                    ]],
                ],
            ],
            'empty' => $totalSales === 0.0 && $totalExpenses === 0.0,
        ];
    }
}
