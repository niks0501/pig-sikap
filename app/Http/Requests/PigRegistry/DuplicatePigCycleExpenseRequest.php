<?php

namespace App\Http\Requests\PigRegistry;

use Illuminate\Foundation\Http\FormRequest;

class DuplicatePigCycleExpenseRequest extends FormRequest
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
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
