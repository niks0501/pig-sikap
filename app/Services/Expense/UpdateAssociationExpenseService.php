<?php

namespace App\Services\Expense;

use App\Models\AssociationExpense;
use App\Models\User;
use App\Services\PigRegistry\ExpenseAmountResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class UpdateAssociationExpenseService
{
    public function __construct(
        private readonly ExpenseAmountResolver $expenseAmountResolver
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(AssociationExpense $expense, array $payload, User $actor): AssociationExpense
    {
        $newReceiptPath = null;
        $oldReceiptPathToDelete = null;

        try {
            $updatedExpense = DB::transaction(function () use ($expense, $payload, $actor, &$newReceiptPath, &$oldReceiptPathToDelete): AssociationExpense {
                $lockedExpense = AssociationExpense::query()
                    ->whereKey($expense->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $receiptPath = $lockedExpense->receipt_path;
                $receipt = $payload['receipt'] ?? null;

                if ($receipt instanceof UploadedFile) {
                    $newReceiptPath = $receipt->store('uploads/expenses/association', 'public');
                    $oldReceiptPathToDelete = $lockedExpense->receipt_path;
                    $receiptPath = $newReceiptPath;
                } elseif ((bool) ($payload['remove_receipt'] ?? false) && is_string($lockedExpense->receipt_path)) {
                    $oldReceiptPathToDelete = $lockedExpense->receipt_path;
                    $receiptPath = null;
                }

                $lockedExpense->update([
                    'item_name' => (string) $payload['item_name'],
                    'category' => (string) $payload['category'],
                    'feed_subcategory' => $payload['feed_subcategory'] ?? null,
                    'quantity' => $payload['quantity'] ?? null,
                    'unit' => $payload['unit'] ?? null,
                    'unit_cost' => $payload['unit_cost'] ?? null,
                    'amount' => $this->expenseAmountResolver->amount($payload),
                    'expense_date' => (string) $payload['expense_date'],
                    'receipt_reference' => $payload['receipt_reference'] ?? null,
                    'receipt_path' => $receiptPath,
                    'supplier_id' => $payload['supplier_id'] ?? null,
                    'canvass_id' => $payload['canvass_id'] ?? null,
                    'fund_source' => $payload['fund_source'] ?? null,
                    'approved_resolution_id' => $payload['approved_resolution_id'] ?? null,
                    'withdrawal_id' => $payload['withdrawal_id'] ?? null,
                    'notes' => (string) $payload['notes'],
                    'updated_by' => $actor->id,
                ]);

                return $lockedExpense->fresh([
                    'supplier:id,name',
                    'approvedResolution:id,title,resolution_number',
                    'withdrawal:id,amount,status',
                    'createdBy:id,name',
                    'updatedBy:id,name',
                ]);
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
