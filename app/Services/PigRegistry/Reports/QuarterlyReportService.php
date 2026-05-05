<?php

namespace App\Services\PigRegistry\Reports;

use App\Models\PigCycleExpense;
use App\Models\PigCycleSale;
use Illuminate\Support\Carbon;

class QuarterlyReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function generate(array $filters): array
    {
        $year = (int) ($filters['year'] ?? now()->year);
        $quarter = (int) ($filters['quarter'] ?? now()->quarter);
        $start = Carbon::createFromDate($year)->firstOfQuarter($quarter);
        $end = $start->copy()->lastOfQuarter();

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
                'period' => sprintf('Q%d %d', $quarter, $year),
                'total_sales' => $totalSales,
                'total_collected' => $totalCollected,
                'total_expenses' => $totalExpenses,
                'net_result' => $net,
            ],
            'rows' => [],
            'empty' => $totalSales === 0.0 && $totalExpenses === 0.0,
        ];
    }
}
