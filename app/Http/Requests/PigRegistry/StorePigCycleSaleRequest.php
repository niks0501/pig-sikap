<?php

namespace App\Http\Requests\PigRegistry;

use App\Models\PigCycle;
use App\Models\PigCycleSale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePigCycleSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        return in_array($user->role?->slug, ['president', 'treasurer'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'batch_id' => ['required', 'integer', 'exists:pig_cycles,id'],
            'buyer_id' => ['nullable', 'integer', 'exists:pig_buyers,id'],
            'buyer_name' => ['required', 'string', 'max:255'],
            'buyer_email' => ['nullable', 'email', 'max:255'],
            'buyer_contact_number' => ['nullable', 'string', 'max:50'],
            'buyer_address' => ['nullable', 'string', 'max:255'],
            'buyer_notes' => ['nullable', 'string', 'max:1000'],
            'pigs_sold' => ['required', 'integer', 'min:1'],
            'sale_date' => ['required', 'date', 'before_or_equal:today'],
            'sale_method' => ['required', 'string', Rule::in(PigCycleSale::SALE_METHODS)],
            'live_weight_kg' => ['required_if:sale_method,live_weight', 'nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'price_per_kg' => ['required_if:sale_method,live_weight', 'nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'price_per_head' => ['required_if:sale_method,per_head', 'nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'payment_status' => ['required', 'string', Rule::in(PigCycleSale::PAYMENT_STATUSES)],
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'receipt_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $cycleId = (int) $this->input('batch_id');
            $pigsSold = (int) $this->input('pigs_sold', 0);

            if ($cycleId > 0) {
                $cycle = PigCycle::query()->find($cycleId);

                if ($cycle instanceof PigCycle && $cycle->isArchived()) {
                    $validator->errors()->add('batch_id', 'Archived cycles cannot accept sales records.');
                }

                if ($cycle instanceof PigCycle && $pigsSold > (int) $cycle->current_count) {
                    $validator->errors()->add('pigs_sold', 'Pigs sold cannot exceed the available count.');
                }
            }

            $saleMethod = (string) $this->input('sale_method');
            $computedAmount = $this->resolveAmount($saleMethod);

            if ($computedAmount <= 0) {
                $validator->errors()->add('amount_paid', 'Sale amount could not be computed. Please review the inputs.');
                return;
            }

            $amountPaid = (float) $this->input('amount_paid', 0);
            $paymentStatus = (string) $this->input('payment_status');

            if ($amountPaid > $computedAmount) {
                $validator->errors()->add('amount_paid', 'Amount paid cannot exceed the total sale amount.');
                return;
            }

            if ($paymentStatus === 'paid' && abs($amountPaid - $computedAmount) > 0.01) {
                $validator->errors()->add('amount_paid', 'Paid status requires the full amount to be received.');
                return;
            }

            if ($paymentStatus === 'pending' && $amountPaid > 0) {
                $validator->errors()->add('amount_paid', 'Pending status requires zero payment received.');
                return;
            }

            if ($paymentStatus === 'partial' && ($amountPaid <= 0 || $amountPaid >= $computedAmount)) {
                $validator->errors()->add('amount_paid', 'Partial status requires a payment between zero and the total amount.');
            }
        });
    }

    private function resolveAmount(string $saleMethod): float
    {
        $saleMethod = trim($saleMethod);
        $pigsSold = (int) $this->input('pigs_sold', 0);

        if ($saleMethod === 'live_weight') {
            $weight = (float) $this->input('live_weight_kg', 0);
            $pricePerKg = (float) $this->input('price_per_kg', 0);

            return round($weight * $pricePerKg, 2);
        }

        if ($saleMethod === 'per_head') {
            $pricePerHead = (float) $this->input('price_per_head', 0);

            return round($pigsSold * $pricePerHead, 2);
        }

        return 0.0;
    }
}
