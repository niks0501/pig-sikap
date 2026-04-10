<?php

namespace App\Http\Requests\PigRegistry;

use App\Models\PigCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePigCycleStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('president') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'new_stage' => ['nullable', 'string', Rule::in(PigCycle::STAGES)],
            'new_status' => ['nullable', 'string', Rule::in(PigCycle::STATUSES)],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('new_stage') && ! $this->filled('new_status')) {
                $validator->errors()->add('new_status', 'Select a new stage or status.');
            }
        });
    }
}
