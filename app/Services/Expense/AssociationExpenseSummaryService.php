<?php

namespace App\Services\Expense;

use App\Models\AssociationExpense;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Provides summary aggregations for association-level expenses
 * grouped by category, fund_source, and feed subcategory.
 */
class AssociationExpenseSummaryService
{
    /**
     * @return array<string, mixed>
     */
    public function buildFromQuery(Builder $query): array
    {
        $baseQuery = clone $query;

        $totalAmount = (float) (clone $baseQuery)->sum('amount');
        $entryCount = (int) (clone $baseQuery)->count();

        $byCategory = (clone $baseQuery)
            ->selectRaw('category, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        $byFundSource = (clone $baseQuery)
            ->selectRaw('fund_source, SUM(amount) as total, COUNT(*) as count')
            ->whereNotNull('fund_source')
            ->groupBy('fund_source')
            ->pluck('total', 'fund_source')
            ->toArray();

        $byFeedSubcategory = (clone $baseQuery)
            ->selectRaw('feed_subcategory, SUM(amount) as total, COUNT(*) as count')
            ->whereNotNull('feed_subcategory')
            ->groupBy('feed_subcategory')
            ->pluck('total', 'feed_subcategory')
            ->toArray();

        $feedTotal = (float) ($byCategory['feed'] ?? 0);
        $feedSharePercent = $totalAmount > 0 ? round(($feedTotal / $totalAmount) * 100, 1) : 0;

        return [
            'total_amount' => $totalAmount,
            'entry_count' => $entryCount,
            'by_category' => $byCategory,
            'by_fund_source' => $byFundSource,
            'by_feed_subcategory' => $byFeedSubcategory,
            'feed_total' => $feedTotal,
            'feed_share_percent' => $feedSharePercent,
        ];
    }

    /**
     * Build month-over-month comparison for association expenses.
     *
     * @return array<string, mixed>
     */
    public function buildMonthComparison(?int $resolutionId = null): array
    {
        $now = now();
        $thisMonthStart = $now->copy()->startOfMonth()->toDateString();
        $lastMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $lastMonthEnd = $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();

        $currentQuery = AssociationExpense::query()
            ->whereDate('expense_date', '>=', $thisMonthStart);

        $lastQuery = AssociationExpense::query()
            ->whereBetween('expense_date', [$lastMonthStart, $lastMonthEnd]);

        if ($resolutionId !== null) {
            $currentQuery->where('approved_resolution_id', $resolutionId);
            $lastQuery->where('approved_resolution_id', $resolutionId);
        }

        $currentTotal = (float) (clone $currentQuery)->sum('amount');
        $currentCount = (int) (clone $currentQuery)->count();
        $lastTotal = (float) (clone $lastQuery)->sum('amount');
        $lastCount = (int) (clone $lastQuery)->count();

        $percentChange = 0;
        if ($lastTotal > 0) {
            $percentChange = round((($currentTotal - $lastTotal) / $lastTotal) * 100, 1);
        }

        return [
            'this_month_total' => $currentTotal,
            'this_month_count' => $currentCount,
            'last_month_total' => $lastTotal,
            'last_month_count' => $lastCount,
            'percent_change' => $percentChange,
        ];
    }
}
