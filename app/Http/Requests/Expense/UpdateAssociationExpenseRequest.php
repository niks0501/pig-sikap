<?php

namespace App\Http\Requests\Expense;

use App\Models\AssociationExpense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class UpdateAssociationExpenseRequest extends FormRequest
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
            'item_name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(AssociationExpense::CATEGORIES)],
            'feed_subcategory' => [
                'nullable',
                'string',
                Rule::in(AssociationExpense::FEED_SUBCATEGORIES),
            ],
            'quantity' => ['nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'unit' => ['nullable', 'string', 'max:50'],
            'unit_cost' => ['nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'amount' => ['nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'receipt_reference' => ['nullable', 'string', 'max:255'],
            'receipt' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'webp', 'pdf'])->max(8 * 1024)],
            'remove_receipt' => ['nullable', 'boolean'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'canvass_id' => ['nullable', 'integer', 'exists:canvasses,id'],
            'fund_source' => ['nullable', 'string', Rule::in(AssociationExpense::FUND_SOURCES)],
            'approved_resolution_id' => ['nullable', 'integer', 'exists:resolutions,id'],
            'withdrawal_id' => ['nullable', 'integer', 'exists:withdrawals,id'],
            'notes' => ['required', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->boolean('remove_receipt') && $this->hasFile('receipt')) {
                $validator->errors()->add('receipt', 'Choose either remove receipt or upload a new one, not both.');
            }

            $this->validateAmountInputs($validator);
        });
    }

    private function validateAmountInputs(Validator $validator): void
    {
        $hasStructuredInput = $this->filled('quantity') || $this->filled('unit') || $this->filled('unit_cost');

        if (! $hasStructuredInput && ! $this->filled('amount')) {
            $validator->errors()->add('amount', 'Enter a direct total amount or provide quantity and unit cost.');

            return;
        }

        if (! $hasStructuredInput) {
            return;
        }

        if (! $this->filled('quantity')) {
            $validator->errors()->add('quantity', 'Quantity is required when using unit cost.');
        }

        if (! $this->filled('unit')) {
            $validator->errors()->add('unit', 'Unit is required when using quantity.');
        }

        if (! $this->filled('unit_cost')) {
            $validator->errors()->add('unit_cost', 'Unit Cost is required when using quantity.');
        }

        if (is_numeric($this->input('quantity')) && is_numeric($this->input('unit_cost'))) {
            $computedAmount = round((float) $this->input('quantity') * (float) $this->input('unit_cost'), 2);

            if ($computedAmount < 0.01 || $computedAmount > 999999.99) {
                $validator->errors()->add('amount', 'Computed Total Amount must be between 0.01 and 999,999.99.');
            }
        }
    }
}
