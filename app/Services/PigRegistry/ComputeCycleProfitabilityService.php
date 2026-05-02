<?php

namespace App\Services\PigRegistry;

use App\Models\PigCycle;
use App\Models\PigCycleExpense;
use App\Models\PigCycleSale;
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
    public function compute(PigCycle $cycle): array
    {
        $salesData = $this->salesSummary($cycle);
        $expenseBreakdown = $this->expenseBreakdown($cycle);
        $totalExpenses = round(array_sum($expenseBreakdown), 2);
        $totalSales = $salesData['total_sales'];
        $totalCollected = $salesData['total_collected'];
        $receivables = $salesData['receivables'];
        $netProfitOrLoss = round($totalSales - $totalExpenses, 2);
        $distributableProfit = round(max($netProfitOrLoss, 0.0), 2);

        return [
            'gross_income' => $totalSales,
            'total_sales' => $totalSales,
            'total_collected' => $totalCollected,
            'receivables' => $receivables,
            'total_expenses' => $totalExpenses,
            'total_cycle_sales' => $totalSales,
            'total_cycle_expense' => $totalExpenses,
            'net_profit_or_loss' => $netProfitOrLoss,
            'distributable_profit' => $distributableProfit,
            'caretaker_share' => round($distributableProfit * self::CARETAKER_SHARE_RATE, 2),
            'member_share' => round($distributableProfit * self::MEMBER_SHARE_RATE, 2),
            'association_share' => round($distributableProfit * self::ASSOCIATION_SHARE_RATE, 2),
            'association_fund_share' => round($distributableProfit * self::ASSOCIATION_SHARE_RATE, 2),
            'expense_breakdown' => $expenseBreakdown,
            'breakdown' => $expenseBreakdown,
            'expense_breakdown_rows' => $this->expenseBreakdownRows($expenseBreakdown),
            'sales_breakdown_rows' => $salesData['breakdown_rows'],
            'sales_summary' => $salesData,
            'status' => $this->status($totalSales, $totalExpenses, $netProfitOrLoss),
            'has_sales' => $totalSales > 0,
            'has_expenses' => $totalExpenses > 0,
            'has_receivables' => $receivables > 0,
            'has_pending_payments' => $salesData['pending_count'] > 0 || $salesData['partial_count'] > 0,
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
     * @return array<string, mixed>
     */
    public function salesSummary(PigCycle $cycle): array
    {
        $sales = $cycle->sales()->get();

        $totalSales = round((float) $sales->sum('amount'), 2);
        $totalCollected = round((float) $sales->sum('amount_paid'), 2);
        $receivables = round($totalSales - $totalCollected, 2);

        $paidCount = 0;
        $pendingCount = 0;
        $partialCount = 0;

        foreach ($sales as $sale) {
            match ($sale->payment_status) {
                PigCycleSale::PAYMENT_STATUSES[0] => $paidCount++,
                PigCycleSale::PAYMENT_STATUSES[2] => $pendingCount++,
                PigCycleSale::PAYMENT_STATUSES[1] => $partialCount++,
                default => null,
            };
        }

        $breakdownRows = $sales->groupBy('sale_method')
            ->map(fn ($group): array => [
                'method' => $group->first()->sale_method ?? 'unclassified',
                'label' => $this->saleMethodLabel($group->first()->sale_method),
                'pigs_sold' => (int) $group->sum('pigs_sold'),
                'total' => round((float) $group->sum('amount'), 2),
            ])
            ->values()
            ->all();

        $totalLiveWeight = round((float) $sales->sum('live_weight_kg'), 2);
        $averagePricePerKg = $totalLiveWeight > 0
            ? round((float) $sales->where('sale_method', 'live_weight')->avg('price_per_kg'), 2)
            : null;

        return [
            'total_sales' => $totalSales,
            'total_collected' => $totalCollected,
            'receivables' => $receivables,
            'sale_count' => $sales->count(),
            'paid_count' => $paidCount,
            'pending_count' => $pendingCount,
            'partial_count' => $partialCount,
            'total_live_weight_kg' => $totalLiveWeight,
            'average_price_per_kg' => $averagePricePerKg,
            'breakdown_rows' => $breakdownRows,
        ];
    }

    /**
     * @return array<string, float>
     */
    public function expenseBreakdown(PigCycle $cycle): array
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
     * @deprecated Alias for compute(). Use compute() instead.
     *
     * @return array<string, mixed>
     */
    public function handle(PigCycle $cycle): array
    {
        return $this->compute($cycle);
    }

    /**
     * Compute a deterministic SHA-256 source hash from normalized cycle financial records.
     */
    public function computeSourceHash(PigCycle $cycle, string $formulaVersion = '2026-05-cycle-profitability-v1'): string
    {
        $salesData = $cycle->sales()
            ->select(['id', 'amount', 'amount_paid', 'payment_status', 'sale_date', 'sale_method', 'pigs_sold', 'live_weight_kg', 'updated_at'])
            ->orderBy('id')
            ->get()
            ->map(fn ($sale): array => [
                'id' => $sale->id,
                'amount' => number_format((float) $sale->amount, 2, '.', ''),
                'amount_paid' => number_format((float) $sale->amount_paid, 2, '.', ''),
                'payment_status' => $sale->payment_status,
                'sale_date' => $sale->sale_date?->format('Y-m-d') ?? '',
                'sale_method' => $sale->sale_method,
                'pigs_sold' => (int) $sale->pigs_sold,
                'live_weight_kg' => number_format((float) $sale->live_weight_kg, 2, '.', ''),
                'updated_at' => $sale->updated_at?->toIso8601String() ?? '',
            ])
            ->all();

        $expensesData = $cycle->expenses()
            ->select(['id', 'category', 'amount', 'expense_date', 'updated_at'])
            ->orderBy('id')
            ->get()
            ->map(fn ($expense): array => [
                'id' => $expense->id,
                'category' => $expense->category,
                'amount' => number_format((float) $expense->amount, 2, '.', ''),
                'expense_date' => $expense->expense_date?->format('Y-m-d') ?? '',
                'updated_at' => $expense->updated_at?->toIso8601String() ?? '',
            ])
            ->all();

        $payload = [
            'pig_cycle_id' => $cycle->id,
            'batch_code' => $cycle->batch_code,
            'cycle_status' => $cycle->status,
            'share_rule' => [
                'caretaker' => self::CARETAKER_SHARE_RATE,
                'members' => self::MEMBER_SHARE_RATE,
                'association' => self::ASSOCIATION_SHARE_RATE,
            ],
            'formula_version' => $formulaVersion,
            'sales' => $salesData,
            'expenses' => $expensesData,
        ];

        $normalizedJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash('sha256', (string) $normalizedJson);
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