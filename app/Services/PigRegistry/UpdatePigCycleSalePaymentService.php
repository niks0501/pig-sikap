<?php

namespace App\Services\PigRegistry;

use App\Models\PigCycleSale;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class UpdatePigCycleSalePaymentService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(PigCycleSale $sale, array $payload, User $actor): PigCycleSale
    {
        $newReceiptPath = null;
        $oldReceiptPathToDelete = null;

        try {
            $updatedSale = DB::transaction(function () use ($sale, $payload, $actor, &$newReceiptPath, &$oldReceiptPathToDelete): PigCycleSale {
                $lockedSale = PigCycleSale::query()
                    ->whereKey($sale->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $receiptPath = $lockedSale->receipt_path;
                $receipt = $payload['receipt'] ?? null;

                if ($receipt instanceof UploadedFile) {
                    $newReceiptPath = $receipt->store('uploads/sales', 'public');
                    $oldReceiptPathToDelete = $lockedSale->receipt_path;
                    $receiptPath = $newReceiptPath;
                } elseif ((bool) ($payload['remove_receipt'] ?? false) && is_string($lockedSale->receipt_path)) {
                    $oldReceiptPathToDelete = $lockedSale->receipt_path;
                    $receiptPath = null;
                }

                $paymentStatus = array_key_exists('payment_status', $payload)
                    ? $this->normalizePaymentStatus($payload['payment_status'])
                    : (string) $lockedSale->payment_status;

                $amountPaid = array_key_exists('amount_paid', $payload)
                    ? round((float) $payload['amount_paid'], 2)
                    : (float) $lockedSale->amount_paid;

                $this->assertPaymentRules($paymentStatus, $amountPaid, (float) $lockedSale->amount);

                $receiptReference = $lockedSale->receipt_reference;
                if (array_key_exists('receipt_reference', $payload)) {
                    $receiptReference = $this->normalizeString($payload['receipt_reference'] ?? null);
                }

                $notes = $lockedSale->notes;
                if (array_key_exists('notes', $payload)) {
                    $notes = $this->normalizeString($payload['notes'] ?? null);
                }

                $lockedSale->update([
                    'payment_status' => $paymentStatus,
                    'amount_paid' => $amountPaid,
                    'receipt_reference' => $receiptReference,
                    'receipt_path' => $receiptPath,
                    'notes' => $notes,
                    'updated_by' => $actor->id,
                ]);

                return $lockedSale->fresh([
                    'cycle:id,batch_code,status,stage,current_count',
                    'buyer:id,name,contact_number,address',
                    'createdBy:id,name',
                    'updatedBy:id,name',
                ]);
            });

            if (is_string($oldReceiptPathToDelete) && $oldReceiptPathToDelete !== '') {
                Storage::disk('public')->delete($oldReceiptPathToDelete);
            }

            return $updatedSale;
        } catch (Throwable $exception) {
            if (is_string($newReceiptPath) && $newReceiptPath !== '') {
                Storage::disk('public')->delete($newReceiptPath);
            }

            throw $exception;
        }
    }

    private function normalizePaymentStatus(mixed $value): string
    {
        $status = $this->normalizeString($value) ?? '';

        if (! in_array($status, PigCycleSale::PAYMENT_STATUSES, true)) {
            throw ValidationException::withMessages([
                'payment_status' => 'The selected payment status is invalid.',
            ]);
        }

        return $status;
    }

    private function assertPaymentRules(string $paymentStatus, float $amountPaid, float $amount): void
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Sale amount could not be computed. Please review the inputs.',
            ]);
        }

        if ($amountPaid < 0) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Amount paid cannot be negative.',
            ]);
        }

        if ($amountPaid > $amount) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Amount paid cannot exceed the total sale amount.',
            ]);
        }

        if ($paymentStatus === 'paid' && abs($amountPaid - $amount) > 0.01) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Paid status requires the full amount to be received.',
            ]);
        }

        if ($paymentStatus === 'pending' && $amountPaid > 0) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Pending status requires zero payment received.',
            ]);
        }

        if ($paymentStatus === 'partial' && ($amountPaid <= 0 || $amountPaid >= $amount)) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Partial status requires a payment between zero and the total amount.',
            ]);
        }
    }

    private function normalizeString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
