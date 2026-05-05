<?php

namespace App\Services\Expense;

use App\Models\AssociationExpense;
use App\Models\User;
use App\Services\PigRegistry\ExpenseAmountResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RecordAssociationExpenseService
{
    public function __construct(
        private readonly ExpenseAmountResolver $expenseAmountResolver
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, User $actor): AssociationExpense
    {
        $storedReceiptPath = null;

        try {
            return DB::transaction(function () use ($payload, $actor, &$storedReceiptPath): AssociationExpense {
                $receiptPath = null;
                $receipt = $payload['receipt'] ?? null;

                if ($receipt instanceof UploadedFile) {
                    $receiptPath = $receipt->store('uploads/expenses/association', 'public');
                    $storedReceiptPath = $receiptPath;
                }

                $expense = AssociationExpense::query()->create([
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
                    'created_by' => $actor->id,
                ]);

                return $expense->load([
                    'supplier:id,name',
                    'approvedResolution:id,title,resolution_number',
                    'withdrawal:id,amount,status',
                    'createdBy:id,name',
                ]);
            });
        } catch (Throwable $exception) {
            if (is_string($storedReceiptPath) && $storedReceiptPath !== '') {
                Storage::disk('public')->delete($storedReceiptPath);
            }

            throw $exception;
        }
    }
}
