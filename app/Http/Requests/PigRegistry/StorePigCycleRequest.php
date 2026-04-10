<?php

namespace App\Http\Requests\PigRegistry;

use App\Models\PigCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePigCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('president') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'has_pig_profiles' => $this->boolean('has_pig_profiles'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'batch_code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('pig_cycles', 'batch_code')->withoutTrashed(),
            ],
            'caretaker_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'cycle_number' => ['nullable', 'integer', 'min:1', 'max:999'],
            'date_of_purchase' => ['required', 'date'],
            'initial_count' => ['required', 'integer', 'min:1'],
            'average_weight' => ['nullable', 'numeric', 'min:0'],
            'stage' => ['required', 'string', Rule::in(PigCycle::STAGES)],
            'status' => ['required', 'string', Rule::in(PigCycle::STATUSES)],
            'has_pig_profiles' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
