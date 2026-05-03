<?php

namespace App\Http\Requests\Workflow;

use App\Models\Resolution;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates withdrawal request data – enforces ≤ approved amount.
 */
class StoreWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $slug = $this->user()?->role?->slug;

        return in_array($slug, ['secretary', 'treasurer', 'president'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Route model binding gives us the Resolution object directly.
        // Guard against the case where it might still be an ID (e.g. unit tests).
        $resolution = $this->route('resolution');

        if (! $resolution instanceof \App\Models\Resolution) {
            $resolution = \App\Models\Resolution::find($resolution);
        }

        $maxAmount = $resolution ? $resolution->remaining_balance : 0;

        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:' . $maxAmount],
            'bank_account' => ['nullable', 'string', 'max:255'],
            'proof_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Please enter the withdrawal amount.',
            'amount.min' => 'Amount must be at least ₱0.01.',
            'amount.max' => 'Amount cannot exceed the remaining approved balance.',
        ];
    }
}
