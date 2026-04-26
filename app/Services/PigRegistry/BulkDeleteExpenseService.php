<?php

namespace App\Services\PigRegistry;

use App\Models\PigCycleExpense;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BulkDeleteExpenseService
{
    /**
     * @param Collection<int, PigCycleExpense> $expenses
     */
    public function handle(Collection $expenses): int
    {
        if ($expenses->isEmpty()) {
            return 0;
        }

        $deletedCount = 0;
        $receiptPathsToDelete = [];

        DB::transaction(function () use ($expenses, &$deletedCount, &$receiptPathsToDelete): void {
            $expenseIds = $expenses->pluck('id')->all();

            $lockedExpenses = PigCycleExpense::query()
                ->with('cycle:id,status,stage')
                ->whereIn('id', $expenseIds)
                ->lockForUpdate()
                ->get();

            $archivedExpenseIds = $lockedExpenses
                ->filter(fn (PigCycleExpense $expense): bool => $expense->cycle?->isArchived() ?? false)
                ->pluck('id')
                ->all();

            if (count($archivedExpenseIds) > 0) {
                throw ValidationException::withMessages([
                    'expense' => 'Expenses linked to archived cycles cannot be deleted. Found ' . count($archivedExpenseIds) . ' protected expense(s).',
                ]);
            }

            foreach ($lockedExpenses as $expense) {
                if (is_string($expense->receipt_path) && $expense->receipt_path !== '') {
                    $receiptPathsToDelete[] = $expense->receipt_path;
                }
            }

            $deletedCount = PigCycleExpense::query()
                ->whereIn('id', $expenseIds)
                ->delete();
        });

        foreach ($receiptPathsToDelete as $path) {
            Storage::disk('public')->delete($path);
        }

        return $deletedCount;
    }
}
