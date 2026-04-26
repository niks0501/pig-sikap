<?php

namespace App\Services\PigRegistry;

use App\Models\PigCycleExpense;
use Illuminate\Database\Eloquent\Builder;

class ExpenseSummaryService
{
    /**
     * @return array<string, mixed>
     */
    public function buildFromQuery(Builder $query): array
    {
        $totalAmount = round((float) (clone $query)->sum('amount'), 2);
        $entryCount = (int) (clone $query)->count();

        /** @var array<string, float> $totalsByCategory */
        $totalsByCategory = (clone $query)
            ->reorder()
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->map(fn ($value): float => round((float) $value, 2))
            ->all();

        $normalizedBreakdown = [];

        foreach (PigCycleExpense::CATEGORIES as $category) {
            $normalizedBreakdown[$category] = $totalsByCategory[$category] ?? 0.0;
        }

        $feedTotal = $normalizedBreakdown['feed'] ?? 0.0;
        $feedShare = $totalAmount > 0 ? round(($feedTotal / $totalAmount) * 100, 2) : 0.0;

        return [
            'total_amount' => $totalAmount,
            'entry_count' => $entryCount,
            'by_category' => $normalizedBreakdown,
            'feed_share_percent' => $feedShare,
            'top_categories' => $this->topCategories($normalizedBreakdown),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildMonthComparison(?int $cycleId = null): array
    {
        $thisMonthStart = now()->startOfMonth()->toDateString();
        $thisMonthEnd = now()->endOfMonth()->toDateString();
        $lastMonthStart = now()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $lastMonthEnd = now()->subMonthNoOverflow()->endOfMonth()->toDateString();

        $baseQuery = PigCycleExpense::query();

        if ($cycleId !== null && $cycleId > 0) {
            $baseQuery->where('batch_id', $cycleId);
        }

        $thisMonthTotal = round((float) (clone $baseQuery)->whereBetween('expense_date', [$thisMonthStart, $thisMonthEnd])->sum('amount'), 2);
        $lastMonthTotal = round((float) (clone $baseQuery)->whereBetween('expense_date', [$lastMonthStart, $lastMonthEnd])->sum('amount'), 2);
        $difference = round($thisMonthTotal - $lastMonthTotal, 2);

        $percentChange = $lastMonthTotal > 0
            ? round(($difference / $lastMonthTotal) * 100, 2)
            : ($thisMonthTotal > 0 ? 100.0 : 0.0);

        return [
            'this_month_total' => $thisMonthTotal,
            'last_month_total' => $lastMonthTotal,
            'difference' => $difference,
            'percent_change' => $percentChange,
            'trend' => $difference > 0 ? 'up' : ($difference < 0 ? 'down' : 'flat'),
        ];
    }

    /**
     * @param  array<string, float>  $breakdown
     * @return list<array<string, mixed>>
     */
    private function topCategories(array $breakdown): array
    {
        return collect($breakdown)
            ->map(fn (float $amount, string $category): array => [
                'category' => $category,
                'label' => PigCycleExpense::categoryLabels()[$category] ?? ucfirst($category),
                'amount' => round($amount, 2),
            ])
            ->sortByDesc('amount')
            ->filter(fn (array $item): bool => $item['amount'] > 0)
            ->take(3)
            ->values()
            ->all();
    }
}
