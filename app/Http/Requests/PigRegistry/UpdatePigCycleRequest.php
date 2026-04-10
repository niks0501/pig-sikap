<?php

namespace App\Http\Requests\PigRegistry;

use App\Models\PigCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePigCycleRequest extends FormRequest
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
            'caretaker_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'cycle_number' => ['nullable', 'integer', 'min:1', 'max:999'],
            'average_weight' => ['nullable', 'numeric', 'min:0'],
            'stage' => ['required', 'string', Rule::in(PigCycle::STAGES)],
            'status' => ['required', 'string', Rule::in(PigCycle::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'initial_count' => ['prohibited'],
            'current_count' => ['prohibited'],
        ];
    }
}
