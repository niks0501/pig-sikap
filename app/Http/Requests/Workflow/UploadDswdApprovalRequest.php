<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates uploaded DSWD approval documents.
 */
class UploadDswdApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('uploadDswdApproval', $this->route('resolution'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dswd_approval_file' => [
                'required',
                'file',
                'mimes:pdf,doc,docx',
                'max:10240', // 10MB
            ],
            'approval_notes' => ['nullable', 'string', 'max:1000'],
            'dswd_reference_number' => ['nullable', 'string', 'max:100'],
            'approval_date' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dswd_approval_file.required' => 'Please upload the DSWD approval document.',
            'dswd_approval_file.mimes' => 'The DSWD approval must be a PDF, DOC, or DOCX file.',
            'dswd_approval_file.max' => 'The DSWD approval document must not exceed 10MB.',
        ];
    }
}
