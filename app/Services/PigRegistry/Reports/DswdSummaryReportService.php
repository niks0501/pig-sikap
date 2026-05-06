<?php

namespace App\Services\PigRegistry\Reports;

use App\Models\PigCycle;
use App\Models\PigCycleExpense;
use App\Models\PigCycleSale;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DswdSummaryReportService
{
    /**
     * Generate a DSWD/LGU-ready compliance summary report with
     * association overview, program statistics, and financial highlights.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function generate(array $filters): array
    {
        $activeCycles = PigCycle::activeRecords()->count();
        $completedCycles = PigCycle::query()
            ->whereIn('status', PigCycle::ARCHIVED_STATUSES)
            ->count();
        $totalCycles = $activeCycles + $completedCycles;

        $totalInitialPigs = (int) PigCycle::query()->sum('initial_count');
        $totalCurrentPigs = (int) PigCycle::activeRecords()->sum('current_count');

        $totalSales = round((float) PigCycleSale::query()->sum('amount'), 2);
        $totalCollected = round((float) PigCycleSale::query()->sum('amount_paid'), 2);
        $totalExpenses = round((float) PigCycleExpense::query()->sum('amount'), 2);
        $netOverall = round($totalSales - $totalExpenses, 2);

        $totalReceivables = round($totalSales - $totalCollected, 2);

        $totalPigsSold = (int) PigCycleSale::query()->sum('pigs_sold');
        $totalBuyers = PigCycleSale::query()->distinct('buyer_id')->count('buyer_id');

        $salesByCycle = PigCycleSale::query()
            ->selectRaw('batch_id, SUM(amount) as total_sales, SUM(amount_paid) as total_collected, SUM(pigs_sold) as pigs_sold')
            ->groupBy('batch_id')
            ->with('cycle:id,batch_code,status')
            ->get()
            ->map(function ($row): array {
                $cycleExpenses = PigCycleExpense::query()
                    ->where('batch_id', $row->batch_id)
                    ->sum('amount');

                return [
                    'cycle_code' => $row->cycle?->batch_code ?? 'N/A',
                    'status' => $row->cycle?->status ?? '',
                    'pigs_sold' => (int) $row->pigs_sold,
                    'total_sales' => round((float) $row->total_sales, 2),
                    'total_collected' => round((float) $row->total_collected, 2),
                    'total_expenses' => round((float) $cycleExpenses, 2),
                    'net' => round((float) ($row->total_sales - $cycleExpenses), 2),
                ];
            })
            ->values()
            ->all();

        $expenseByCategory = PigCycleExpense::query()
            ->get()
            ->groupBy('category')
            ->map(function ($items) {
                $catTotal = round((float) $items->sum('amount'), 2);

                return [
                    'category' => PigCycleExpense::categoryLabels()[$items->first()->category] ?? ucfirst((string) $items->first()->category),
                    'amount' => $catTotal,
                ];
            })
            ->values()
            ->all();

        $totalMembers = User::query()
            ->whereHas('role', fn ($q) => $q->where('slug', 'member'))
            ->where('is_active', true)
            ->count();

        $totalOfficers = User::query()
            ->whereHas('role', fn ($q) => $q->whereIn('slug', ['president', 'treasurer', 'secretary']))
            ->where('is_active', true)
            ->count();

        $associationName = 'Elite Visionaries of Humayingan SLP Association';
        $associationAddress = 'Brgy. Humayingan, Lian, Batangas';

        return [
            'summary' => [
                'association_name' => $associationName,
                'association_address' => $associationAddress,
                'total_cycles' => $totalCycles,
                'active_cycles' => $activeCycles,
                'completed_cycles' => $completedCycles,
                'total_initial_pigs' => $totalInitialPigs,
                'total_current_pigs' => $totalCurrentPigs,
                'total_members' => $totalMembers,
                'total_officers' => $totalOfficers,
                'total_sales' => $totalSales,
                'total_collected' => $totalCollected,
                'total_expenses' => $totalExpenses,
                'total_receivables' => $totalReceivables,
                'net_overall' => $netOverall,
                'total_pigs_sold' => $totalPigsSold,
                'total_buyers' => $totalBuyers,
            ],
            'sales_by_cycle' => $salesByCycle,
            'expense_by_category' => $expenseByCategory,
            'charts' => [
                'salesVsExpenses' => [
                    'labels' => ['Total Sales', 'Total Collected', 'Total Expenses'],
                    'datasets' => [[
                        'data' => [$totalSales, $totalCollected, $totalExpenses],
                        'backgroundColor' => ['#0c6d57', '#8edcbc', '#f87171'],
                    ]],
                ],
            ],
            'empty' => $totalCycles === 0,
        ];
    }
}
