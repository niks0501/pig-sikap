<?php

namespace App\Services\PigRegistry;

use App\Models\PigCycle;
use App\Models\PigCycleExpense;
use Illuminate\Support\Facades\DB;

class ComputeCycleProfitabilityService
{
    private const CARETAKER_SHARE_RATE = 0.50;

    private const MEMBER_SHARE_RATE = 0.25;

    private const ASSOCIATION_SHARE_RATE = 0.25;

    /**
     * Compute the financial result of one pig cycle from encoded sales and expenses.
     *
     * @return array<string, mixed>
     */
    public function handle(PigCycle $cycle): array
    {
        $expenseBreakdown = $this->expenseBreakdown($cycle);
        $totalExpenses = round(array_sum($expenseBreakdown), 2);
        $totalSales = round((float) $cycle->sales()->sum('amount'), 2);
        $netProfitOrLoss = round($totalSales - $totalExpenses, 2);
        $distributableProfit = round(max($netProfitOrLoss, 0.0), 2);

        return [
            'gross_income' => $totalSales,
            'total_sales' => $totalSales,
            'total_expenses' => $totalExpenses,
            'total_cycle_sales' => $totalSales,
            'total_cycle_expense' => $totalExpenses,
            'net_profit_or_loss' => $netProfitOrLoss,
            'distributable_profit' => $distributableProfit,
            'caretaker_share' => round($distributableProfit * self::CARETAKER_SHARE_RATE, 2),
            'member_share' => round($distributableProfit * self::MEMBER_SHARE_RATE, 2),
            'association_share' => round($distributableProfit * self::ASSOCIATION_SHARE_RATE, 2),
            'expense_breakdown' => $expenseBreakdown,
            'breakdown' => $expenseBreakdown,
            'expense_breakdown_rows' => $this->expenseBreakdownRows($expenseBreakdown),
            'sales_breakdown_rows' => $this->salesBreakdownRows($cycle),
            'status' => $this->status($totalSales, $totalExpenses, $netProfitOrLoss),
            'has_sales' => $totalSales > 0,
            'has_expenses' => $totalExpenses > 0,
            'share_rule' => [
                'caretaker' => self::CARETAKER_SHARE_RATE,
                'members' => self::MEMBER_SHARE_RATE,
                'association' => self::ASSOCIATION_SHARE_RATE,
            ],
            'computation_version' => '2026-05-cycle-profitability-v1',
            'is_finalized' => false,
        ];
    }

    /**
     * @return array<string, float>
     */
    private function expenseBreakdown(PigCycle $cycle): array
    {
        $grouped = $cycle->expenses()
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->pluck('total', 'category');

        $breakdown = [];

        foreach (PigCycleExpense::CATEGORIES as $category) {
            $breakdown[$category] = round((float) ($grouped[$category] ?? 0), 2);
        }

        return $breakdown;
    }

    /**
     * @param  array<string, float>  $breakdown
     * @return list<array<string, mixed>>
     */
    private function expenseBreakdownRows(array $breakdown): array
    {
        $labels = PigCycleExpense::categoryLabels();

        return collect($breakdown)
            ->map(fn (float $total, string $category): array => [
                'category' => $category,
                'label' => $labels[$category] ?? ucfirst($category),
                'total' => round($total, 2),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function salesBreakdownRows(PigCycle $cycle): array
    {
        return $cycle->sales()
            ->select('sale_method', DB::raw('SUM(amount) as total'), DB::raw('SUM(pigs_sold) as pigs_sold'))
            ->groupBy('sale_method')
            ->get()
            ->map(fn ($row): array => [
                'method' => $row->sale_method ?? 'unclassified',
                'label' => $this->saleMethodLabel($row->sale_method),
                'pigs_sold' => (int) $row->pigs_sold,
                'total' => round((float) $row->total, 2),
            ])
            ->values()
            ->all();
    }

    private function saleMethodLabel(?string $method): string
    {
        return match ($method) {
            'live_weight' => 'Live Weight Sales',
            'per_head' => 'Per Head Sales',
            default => 'Other Sales',
        };
    }

    private function status(float $totalSales, float $totalExpenses, float $netProfitOrLoss): string
    {
        if ($totalSales <= 0) {
            return 'zero_sales';
        }

        if ($netProfitOrLoss < 0) {
            return 'loss';
        }

        if ($netProfitOrLoss === 0.0 && $totalExpenses > 0) {
            return 'break_even';
        }

        return 'profit';
    }
}
