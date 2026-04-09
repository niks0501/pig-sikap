<?php

namespace App\Http\Requests\PigRegistry;

use App\Models\PigBatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePigBatchRequest extends FormRequest
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
            'breeder_id' => ['nullable', 'integer', 'exists:pig_breeders,id'],
            'caretaker_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'cycle_number' => ['nullable', 'integer', 'min:1', 'max:999'],
            'average_weight' => ['nullable', 'numeric', 'min:0'],
            'stage' => ['required', 'string', Rule::in(PigBatch::STAGES)],
            'status' => ['required', 'string', Rule::in(PigBatch::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'initial_count' => ['prohibited'],
            'current_count' => ['prohibited'],
        ];
    }
}
