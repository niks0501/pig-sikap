<?php

namespace App\Http\Requests\PigRegistry;

use App\Models\PigCycle;
use App\Models\PigCycleExpense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class StorePigCycleExpenseRequest extends FormRequest
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
            'batch_id' => ['required', 'integer', 'exists:pig_cycles,id'],
            'category' => ['required', 'string', Rule::in(PigCycleExpense::CATEGORIES)],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['required', 'string', 'max:1000'],
            'receipt' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'webp', 'pdf'])->max(8 * 1024)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $cycleId = (int) $this->input('batch_id');

            if ($cycleId <= 0) {
                return;
            }

            $cycle = PigCycle::query()->find($cycleId);

            if ($cycle instanceof PigCycle && $cycle->isArchived()) {
                $validator->errors()->add('batch_id', 'Archived cycles cannot accept new expense records.');
            }
        });
    }
}
