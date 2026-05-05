<?php

namespace App\Http\Requests\PigRegistry;

use Illuminate\Foundation\Http\FormRequest;

class FinalizeCycleProfitabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('president') ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:1000'],
            're_finalize' => ['nullable', 'boolean'],
            're_finalize_reason_code' => ['nullable', 'string', 'required_if:re_finalize,true,1'],
            're_finalize_reason_notes' => ['nullable', 'string', 'min:10', 'max:1000', 'required_if:re_finalize,true,1'],
            'loss_acknowledged' => ['nullable', 'boolean', 'accepted_if:has_loss,true,1'],
            'receivables_acknowledged' => ['nullable', 'boolean', 'accepted_if:has_receivables,true,1'],
            'member_distributions' => ['nullable', 'array'],
            'member_distributions.*.user_id' => ['required_with:member_distributions', 'integer', 'exists:users,id'],
            'member_distributions.*.allocated_amount' => ['required_with:member_distributions', 'numeric', 'min:0'],
            'member_distributions.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        // Cast checkbox strings to boolean
        if (isset($data['loss_acknowledged'])) {
            $data['loss_acknowledged'] = filter_var($data['loss_acknowledged'], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($data['receivables_acknowledged'])) {
            $data['receivables_acknowledged'] = filter_var($data['receivables_acknowledged'], FILTER_VALIDATE_BOOLEAN);
        }

        return $data;
    }

    public function messages(): array
    {
        return [
            're_finalize_reason_code.required_if' => 'A reason is required when re-finalizing a profitability snapshot.',
            're_finalize_reason_notes.required_if' => 'Please explain the reason for re-finalization.',
            're_finalize_reason_notes.min' => 'The re-finalization reason must be at least 10 characters.',
            'loss_acknowledged.accepted_if' => 'You must confirm that this cycle has a net loss and no profit will be distributed.',
            'receivables_acknowledged.accepted_if' => 'You must acknowledge the unpaid receivables before finalizing.',
        ];
    }
}