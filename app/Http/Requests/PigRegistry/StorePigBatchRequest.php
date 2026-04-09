<?php

namespace App\Http\Requests\PigRegistry;

use App\Models\PigBatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePigBatchRequest extends FormRequest
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
                Rule::unique('pig_batches', 'batch_code')->withoutTrashed(),
            ],
            'breeder_id' => ['nullable', 'integer', 'exists:pig_breeders,id'],
            'caretaker_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'cycle_number' => ['nullable', 'integer', 'min:1', 'max:999'],
            'birth_date' => ['required', 'date'],
            'initial_count' => ['required', 'integer', 'min:1'],
            'average_weight' => ['nullable', 'numeric', 'min:0'],
            'stage' => ['required', 'string', Rule::in(PigBatch::STAGES)],
            'status' => ['required', 'string', Rule::in(PigBatch::STATUSES)],
            'has_pig_profiles' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
