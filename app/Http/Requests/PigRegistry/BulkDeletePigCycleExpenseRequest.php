<?php

namespace App\Http\Requests\PigRegistry;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeletePigCycleExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole('president');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:pig_cycle_expenses,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'No expenses selected for deletion.',
            'ids.min' => 'No expenses selected for deletion.',
        ];
    }
}
