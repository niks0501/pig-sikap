<?php

namespace App\Http\Requests\PigRegistry;

use App\Models\PigCycleExpense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpensePreferenceRequest extends FormRequest
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
            'last_category' => ['sometimes', 'nullable', 'string', Rule::in(PigCycleExpense::CATEGORIES)],
            'last_cycle_id' => ['sometimes', 'nullable', 'integer', 'exists:pig_cycles,id'],
            'last_expense_date' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'preset_amounts' => ['sometimes', 'array', 'max:6'],
            'preset_amounts.*' => ['numeric', 'min:1', 'max:999999'],
        ];
    }
}
