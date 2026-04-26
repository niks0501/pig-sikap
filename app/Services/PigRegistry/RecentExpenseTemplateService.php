<?php

namespace App\Services\PigRegistry;

use App\Models\PigCycleExpense;
use App\Models\User;

class RecentExpenseTemplateService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function forUser(User $user, int $limit = 5): array
    {
        return PigCycleExpense::query()
            ->with('cycle:id,batch_code,status,stage')
            ->where('created_by', $user->id)
            ->latest('expense_date')
            ->latest('id')
            ->limit(30)
            ->get(['id', 'batch_id', 'category', 'amount', 'expense_date', 'notes'])
            ->unique(fn (PigCycleExpense $expense): string => implode('|', [
                $expense->batch_id,
                $expense->category,
                (string) $expense->amount,
                trim((string) $expense->notes),
            ]))
            ->take($limit)
            ->values()
            ->map(fn (PigCycleExpense $expense): array => [
                'id' => $expense->id,
                'batch_id' => $expense->batch_id,
                'cycle_code' => $expense->cycle?->batch_code,
                'category' => $expense->category,
                'category_label' => $expense->categoryLabel(),
                'amount' => (float) $expense->amount,
                'expense_date' => $expense->expense_date?->toDateString(),
                'notes' => $expense->notes,
            ])
            ->all();
    }
}
