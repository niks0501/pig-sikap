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
        $year = (int) ($filters['year'] ?? now()->year);
        $month = (int) ($filters['month'] ?? now()->month);
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

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

        return [
            'summary' => [
                'period' => $start->format('F Y'),
                'total_sales' => $totalSales,
                'total_collected' => $totalCollected,
                'total_expenses' => $totalExpenses,
                'net_result' => $net,
            ],
            'rows' => [],
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
