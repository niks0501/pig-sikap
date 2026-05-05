<?php

namespace App\Services\Expense;

use App\Models\AssociationExpense;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteAssociationExpenseService
{
    public function handle(AssociationExpense $expense): void
    {
        $receiptPath = null;

        DB::transaction(function () use ($expense, &$receiptPath): void {
            $lockedExpense = AssociationExpense::query()
                ->whereKey($expense->id)
                ->lockForUpdate()
                ->firstOrFail();

            $receiptPath = $lockedExpense->receipt_path;
            $lockedExpense->delete();
        });

        if (is_string($receiptPath) && $receiptPath !== '') {
            Storage::disk('public')->delete($receiptPath);
        }
    }
}
