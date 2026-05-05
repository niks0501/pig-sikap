<?php

namespace App\Http\Requests\Reports;

use App\Models\PigCycleExpense;
use App\Models\PigCycleSale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateReportRequest extends FormRequest
{
    /**
     * Merge the route parameter 'type' so it passes validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' => $this->route('type'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['inventory', 'health', 'mortality', 'expense', 'sales', 'monthly', 'quarterly', 'profitability'])],
            'cycle_id' => ['nullable', 'integer', 'exists:pig_cycles,id'],
            'date_range' => ['nullable', Rule::in(['this_month', 'last_month', 'this_quarter', 'this_year', 'custom'])],
            'start_date' => ['required_if:date_range,custom', 'date'],
            'end_date' => ['required_if:date_range,custom', 'date', 'after_or_equal:start_date'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'quarter' => ['nullable', 'integer', 'between:1,4'],
            'year' => ['nullable', 'integer', 'between:2020,2099'],
            'category' => ['nullable', Rule::in(PigCycleExpense::CATEGORIES)],
            'payment_status' => ['nullable', Rule::in(PigCycleSale::PAYMENT_STATUSES)],
            'include_details' => ['nullable', 'boolean'],
            'include_charts' => ['nullable', 'boolean'],
        ];
    }
}
