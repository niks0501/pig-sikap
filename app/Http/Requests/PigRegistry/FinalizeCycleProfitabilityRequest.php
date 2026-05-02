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
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            're_finalize_reason_code.required_if' => 'A reason is required when re-finalizing a profitability snapshot.',
            're_finalize_reason_notes.required_if' => 'Please explain the reason for re-finalization.',
            're_finalize_reason_notes.min' => 'The re-finalization reason must be at least 10 characters.',
        ];
    }
}