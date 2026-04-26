<?php

namespace App\Services\PigRegistry;

use App\Models\PigCycleExpense;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DuplicateExpenseService
{
    public function handle(PigCycleExpense $expense, array $payload, User $actor): PigCycleExpense
    {
        $newExpenseDate = $payload['expense_date'] ?? null;

        if (! $newExpenseDate) {
            throw ValidationException::withMessages([
                'expense_date' => 'New expense date is required for duplication.',
            ]);
        }

        return DB::transaction(function () use ($expense, $newExpenseDate, $actor): PigCycleExpense {
            $lockedExpense = PigCycleExpense::query()
                ->with('cycle:id,status,stage')
                ->whereKey($expense->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedExpense->cycle?->isArchived()) {
                throw ValidationException::withMessages([
                    'expense' => 'Expenses linked to archived cycles cannot be duplicated.',
                ]);
            }

            $newExpense = PigCycleExpense::query()->create([
                'batch_id' => $lockedExpense->batch_id,
                'category' => $lockedExpense->category,
                'amount' => $lockedExpense->amount,
                'expense_date' => $newExpenseDate,
                'notes' => $lockedExpense->notes,
                'receipt_path' => null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            return $newExpense->load(['cycle:id,batch_code,status,stage', 'createdBy:id,name']);
        });
    }
}
