<?php

namespace App\Http\Requests\PigRegistry;

use App\Models\PigCycleSale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePigCycleSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        return in_array($user->role?->slug, ['president', 'treasurer', 'secretary'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payment_status' => ['nullable', 'string', Rule::in(PigCycleSale::PAYMENT_STATUSES)],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'receipt_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $userRole = $this->user()?->role?->slug;
            $editingPayments = $this->filled('payment_status') || $this->filled('amount_paid');

            if ($userRole === 'secretary' && $editingPayments) {
                $validator->errors()->add('payment_status', 'Secretary role cannot edit payment details.');
                return;
            }

            if (! $editingPayments) {
                return;
            }

            /** @var PigCycleSale|null $sale */
            $sale = $this->route('sale');

            if (! $sale instanceof PigCycleSale) {
                return;
            }

            $paymentStatus = (string) $this->input('payment_status', $sale->payment_status);
            $amountPaid = $this->filled('amount_paid')
                ? (float) $this->input('amount_paid')
                : (float) $sale->amount_paid;

            $amount = (float) $sale->amount;

            if ($amountPaid > $amount) {
                $validator->errors()->add('amount_paid', 'Amount paid cannot exceed the total sale amount.');
                return;
            }

            if ($paymentStatus === 'paid' && abs($amountPaid - $amount) > 0.01) {
                $validator->errors()->add('amount_paid', 'Paid status requires the full amount to be received.');
                return;
            }

            if ($paymentStatus === 'pending' && $amountPaid > 0) {
                $validator->errors()->add('amount_paid', 'Pending status requires zero payment received.');
                return;
            }

            if ($paymentStatus === 'partial' && ($amountPaid <= 0 || $amountPaid >= $amount)) {
                $validator->errors()->add('amount_paid', 'Partial status requires a payment between zero and the total amount.');
            }
        });
    }
}
