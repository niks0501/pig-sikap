<?php

namespace App\Services\PigRegistry;

use App\Models\PigCycle;
use App\Models\PigCycleExpense;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class RecordPigCycleExpenseService
{
    public function __construct(
        private readonly ExpenseAmountResolver $expenseAmountResolver
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, User $actor): PigCycleExpense
    {
        $storedReceiptPath = null;

        try {
            return DB::transaction(function () use ($payload, $actor, &$storedReceiptPath): PigCycleExpense {
                $cycle = PigCycle::query()
                    ->whereKey((int) $payload['batch_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($cycle->isArchived()) {
                    throw ValidationException::withMessages([
                        'batch_id' => 'Archived cycles are final and cannot accept new expenses.',
                    ]);
                }

                $receiptPath = null;
                $receipt = $payload['receipt'] ?? null;

                if ($receipt instanceof UploadedFile) {
                    $receiptPath = $receipt->store('uploads/expenses', 'public');
                    $storedReceiptPath = $receiptPath;
                }

                $expense = PigCycleExpense::query()->create([
                    'batch_id' => $cycle->id,
                    'category' => (string) $payload['category'],
                    'quantity' => $payload['quantity'] ?? null,
                    'unit' => $payload['unit'] ?? null,
                    'unit_cost' => $payload['unit_cost'] ?? null,
                    'amount' => $this->expenseAmountResolver->amount($payload),
                    'expense_date' => (string) $payload['expense_date'],
                    'notes' => (string) $payload['notes'],
                    'receipt_path' => $receiptPath,
                    'created_by' => $actor->id,
                ]);

                return $expense->load(['cycle:id,batch_code,status,stage', 'createdBy:id,name']);
            });
        } catch (Throwable $exception) {
            if (is_string($storedReceiptPath) && $storedReceiptPath !== '') {
                Storage::disk('public')->delete($storedReceiptPath);
            }

            throw $exception;
        }
    }
}
