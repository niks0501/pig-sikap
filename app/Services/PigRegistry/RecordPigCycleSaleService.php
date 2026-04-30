<?php

namespace App\Services\PigRegistry;

use App\Models\PigBuyer;
use App\Models\PigCycle;
use App\Models\PigCycleSale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordPigCycleSaleService
{
    public function __construct(
        private readonly CycleInventoryImpactService $cycleInventoryImpactService,
        private readonly UpdatePigCycleStatusService $updatePigCycleStatusService
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, User $actor): PigCycleSale
    {
        return DB::transaction(function () use ($payload, $actor): PigCycleSale {
                $cycle = PigCycle::query()
                    ->whereKey((int) $payload['batch_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($cycle->isArchived()) {
                    throw ValidationException::withMessages([
                        'batch_id' => 'Archived cycles cannot accept sales records.',
                    ]);
                }

                $pigsSold = (int) ($payload['pigs_sold'] ?? 0);

                if ($pigsSold < 1) {
                    throw ValidationException::withMessages([
                        'pigs_sold' => 'Pigs sold must be at least 1.',
                    ]);
                }

                if ($pigsSold > (int) $cycle->current_count) {
                    throw ValidationException::withMessages([
                        'pigs_sold' => 'Pigs sold cannot exceed the available count.',
                    ]);
                }

                $saleMethod = $this->resolveSaleMethod($payload['sale_method'] ?? null);
                $paymentStatus = $this->resolvePaymentStatus($payload['payment_status'] ?? null);
                $amount = $this->resolveAmount($saleMethod, $payload, $pigsSold);
                $amountPaid = round((float) ($payload['amount_paid'] ?? 0), 2);

                $this->assertPaymentRules($paymentStatus, $amountPaid, $amount);

                $buyerId = $this->resolveBuyerId($payload, $actor);

                $digitalReceiptNumber = $this->generateReceiptNumber((string) $payload['sale_date']);

                $sale = PigCycleSale::query()->create([
                    'batch_id' => $cycle->id,
                    'buyer_id' => $buyerId,
                    'pigs_sold' => $pigsSold,
                    'amount' => $amount,
                    'sale_date' => (string) $payload['sale_date'],
                    'sale_method' => $saleMethod,
                    'live_weight_kg' => $saleMethod === 'live_weight' ? (float) ($payload['live_weight_kg'] ?? 0) : null,
                    'price_per_kg' => $saleMethod === 'live_weight' ? (float) ($payload['price_per_kg'] ?? 0) : null,
                    'price_per_head' => $saleMethod === 'per_head' ? (float) ($payload['price_per_head'] ?? 0) : null,
                    'payment_status' => $paymentStatus,
                    'amount_paid' => $amountPaid,
                    'receipt_reference' => $this->normalizeString($payload['receipt_reference'] ?? null),
                    'digital_receipt_number' => $digitalReceiptNumber,
                    'digital_receipt_status' => 'not_sent',
                    'notes' => $this->normalizeString($payload['notes'] ?? null),
                    'created_by' => $actor->id,
                ]);

                $this->cycleInventoryImpactService->applyDelta(
                    $cycle,
                    -$pigsSold,
                    'sale deduction',
                    $actor,
                    [
                        'adjustment_type' => 'decrease',
                        'remarks' => 'Auto-adjusted from sale #'.$sale->id,
                        'source_module' => 'sales',
                        'source_type' => 'pig_cycle_sale',
                        'source_id' => $sale->id,
                        'source_event_key' => 'sale-'.$sale->id,
                    ]
                );

                $cycle->refresh();

                if ((int) $cycle->current_count === 0) {
                    $this->updatePigCycleStatusService->handle(
                        $cycle,
                        [
                            'new_stage' => 'Completed',
                            'new_status' => 'Sold',
                            'remarks' => 'Auto-updated after sale #'.$sale->id,
                        ],
                        $actor,
                        [
                            'transition_origin' => 'sales',
                            'transition_key' => 'sale-'.$sale->id,
                            'context' => [
                                'sale_id' => $sale->id,
                            ],
                        ]
                    );
                }

                return $sale->load([
                    'cycle:id,batch_code,status,stage,current_count',
                    'buyer:id,name,email,contact_number,address',
                    'createdBy:id,name',
                    'updatedBy:id,name',
                ]);
            });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveBuyerId(array $payload, User $actor): ?int
    {
        $buyerId = (int) ($payload['buyer_id'] ?? 0);

        if ($buyerId > 0) {
            $buyer = PigBuyer::query()->find($buyerId);

            if ($buyer && $buyer->email === null) {
                $email = $this->normalizeString($payload['buyer_email'] ?? null);

                if ($email !== null) {
                    $buyer->update([
                        'email' => $email,
                        'updated_by' => $actor->id,
                    ]);
                }
            }

            return $buyerId;
        }

        $name = $this->normalizeString($payload['buyer_name'] ?? null);

        if ($name === null) {
            throw ValidationException::withMessages([
                'buyer_name' => 'Buyer name is required.',
            ]);
        }

        $buyer = PigBuyer::query()->create([
            'name' => $name,
            'email' => $this->normalizeString($payload['buyer_email'] ?? null),
            'contact_number' => $this->normalizeString($payload['buyer_contact_number'] ?? null),
            'address' => $this->normalizeString($payload['buyer_address'] ?? null),
            'notes' => $this->normalizeString($payload['buyer_notes'] ?? null),
            'created_by' => $actor->id,
        ]);

        return $buyer->id;
    }

    private function resolveSaleMethod(mixed $value): string
    {
        $method = $this->normalizeString($value);

        if ($method === null || ! in_array($method, PigCycleSale::SALE_METHODS, true)) {
            throw ValidationException::withMessages([
                'sale_method' => 'The selected sale method is invalid.',
            ]);
        }

        return $method;
    }

    private function resolvePaymentStatus(mixed $value): string
    {
        $status = $this->normalizeString($value);

        if ($status === null || ! in_array($status, PigCycleSale::PAYMENT_STATUSES, true)) {
            throw ValidationException::withMessages([
                'payment_status' => 'The selected payment status is invalid.',
            ]);
        }

        return $status;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveAmount(string $saleMethod, array $payload, int $pigsSold): float
    {
        if ($saleMethod === 'live_weight') {
            $weight = (float) ($payload['live_weight_kg'] ?? 0);
            $pricePerKg = (float) ($payload['price_per_kg'] ?? 0);

            if ($weight <= 0 || $pricePerKg <= 0) {
                throw ValidationException::withMessages([
                    'live_weight_kg' => 'Live weight and price per kg are required to compute the sale total.',
                ]);
            }

            return round($weight * $pricePerKg, 2);
        }

        $pricePerHead = (float) ($payload['price_per_head'] ?? 0);

        if ($pigsSold < 1 || $pricePerHead <= 0) {
            throw ValidationException::withMessages([
                'price_per_head' => 'Price per head is required to compute the sale total.',
            ]);
        }

        return round($pigsSold * $pricePerHead, 2);
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

    private function generateReceiptNumber(string $saleDate): string
    {
        $year = substr($saleDate, 0, 4);

        if (! ctype_digit($year)) {
            $year = now()->format('Y');
        }

        $count = PigCycleSale::query()->whereYear('sale_date', (int) $year)->count() + 1;

        return sprintf('PSR-%s-%05d', $year, $count);
    }
}
