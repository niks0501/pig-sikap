<?php

namespace App\Services\PigRegistry;

use App\Models\PigCycleExpense;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DeletePigCycleExpenseService
{
    public function handle(PigCycleExpense $expense): void
    {
        $receiptPath = null;

        DB::transaction(function () use ($expense, &$receiptPath): void {
            $lockedExpense = PigCycleExpense::query()
                ->with('cycle:id,status,stage')
                ->whereKey($expense->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedExpense->cycle?->isArchived()) {
                throw ValidationException::withMessages([
                    'expense' => 'Expenses linked to archived cycles cannot be deleted.',
                ]);
            }

            $receiptPath = $lockedExpense->receipt_path;
            $lockedExpense->delete();
        });

        if (is_string($receiptPath) && $receiptPath !== '') {
            Storage::disk('public')->delete($receiptPath);
        }
    }
}
