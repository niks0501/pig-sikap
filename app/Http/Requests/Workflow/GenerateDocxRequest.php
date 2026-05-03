<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates options when generating a resolution DOCX draft.
 */
class GenerateDocxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('generateDocuments', $this->route('resolution'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'include_budget_summary' => ['boolean'],
            'include_member_list' => ['boolean'],
        ];
    }
}
