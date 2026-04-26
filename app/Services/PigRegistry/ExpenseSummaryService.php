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
        ];
    }
}
