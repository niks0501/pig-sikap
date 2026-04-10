<?php

namespace App\Http\Requests\PigRegistry;

use App\Models\PigCycleAdjustment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePigCycleAdjustmentRequest extends FormRequest
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
            'adjustment_type' => ['required', 'string', Rule::in(PigCycleAdjustment::ADJUSTMENT_TYPES)],
            'quantity_change' => ['required', 'integer'],
            'quantity_after' => ['nullable', 'integer', 'min:0'],
            'reason' => ['required', 'string', Rule::in(PigCycleAdjustment::REASONS)],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = (string) $this->input('adjustment_type');
            $quantityChange = (int) $this->input('quantity_change', 0);

            if (in_array($type, ['increase', 'decrease'], true) && $quantityChange === 0) {
                $validator->errors()->add('quantity_change', 'Quantity change must not be zero.');
            }

            if (
                $type === 'correction'
                && ! $this->filled('quantity_after')
                && $quantityChange === 0
            ) {
                $validator->errors()->add('quantity_change', 'Provide a quantity delta or resulting count for corrections.');
            }
        });
    }
}
