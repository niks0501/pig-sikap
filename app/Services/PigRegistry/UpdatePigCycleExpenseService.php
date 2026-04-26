<?php

namespace App\Services\PigRegistry;

use App\Models\PigCycle;
use App\Models\PigCycleExpense;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class UpdatePigCycleExpenseService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(PigCycleExpense $expense, array $payload): PigCycleExpense
    {
        $newReceiptPath = null;
        $oldReceiptPathToDelete = null;

        try {
            $updatedExpense = DB::transaction(function () use ($expense, $payload, &$newReceiptPath, &$oldReceiptPathToDelete): PigCycleExpense {
                $lockedExpense = PigCycleExpense::query()
                    ->whereKey($expense->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $cycle = PigCycle::query()
                    ->whereKey((int) $payload['batch_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($cycle->isArchived()) {
                    throw ValidationException::withMessages([
                        'batch_id' => 'Archived cycles are final and cannot be edited.',
                    ]);
                }

                $receiptPath = $lockedExpense->receipt_path;
                $receipt = $payload['receipt'] ?? null;

                if ($receipt instanceof UploadedFile) {
                    $newReceiptPath = $receipt->store('uploads/expenses', 'public');
                    $oldReceiptPathToDelete = $lockedExpense->receipt_path;
                    $receiptPath = $newReceiptPath;
                } elseif ((bool) ($payload['remove_receipt'] ?? false) && is_string($lockedExpense->receipt_path)) {
                    $oldReceiptPathToDelete = $lockedExpense->receipt_path;
                    $receiptPath = null;
                }

                $lockedExpense->update([
                    'batch_id' => $cycle->id,
                    'category' => (string) $payload['category'],
                    'amount' => (float) $payload['amount'],
                    'expense_date' => (string) $payload['expense_date'],
                    'notes' => (string) $payload['notes'],
                    'receipt_path' => $receiptPath,
                ]);

                return $lockedExpense->fresh(['cycle:id,batch_code,status,stage', 'createdBy:id,name']);
            });

            if (is_string($oldReceiptPathToDelete) && $oldReceiptPathToDelete !== '') {
                Storage::disk('public')->delete($oldReceiptPathToDelete);
            }

            return $updatedExpense;
        } catch (Throwable $exception) {
            if (is_string($newReceiptPath) && $newReceiptPath !== '') {
                Storage::disk('public')->delete($newReceiptPath);
            }

            throw $exception;
        }
    }
}
