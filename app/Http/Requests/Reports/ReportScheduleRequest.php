<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportScheduleRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'report_type' => ['required', Rule::in(['expense', 'sales', 'monthly', 'quarterly', 'profitability'])],
            'format' => ['required', Rule::in(['pdf', 'csv'])],
            'frequency' => ['required', Rule::in(['monthly', 'quarterly'])],
            'day_of_month' => ['nullable', 'integer', 'between:1,28'],
            'run_at' => ['nullable', 'date_format:H:i'],
            'cycle_id' => ['nullable', 'integer', 'exists:pig_cycles,id'],
            'filters_json' => ['nullable', 'array'],
            'status' => ['nullable', Rule::in(['active', 'paused'])],
        ];
    }
}
