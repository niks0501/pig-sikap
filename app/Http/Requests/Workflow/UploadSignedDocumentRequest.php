<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates uploaded signed resolution documents.
 */
class UploadSignedDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('uploadSignedDocument', $this->route('resolution'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'signed_document' => [
                'required',
                'file',
                'mimes:pdf',
                'max:10240', // 10MB
            ],
            'signature_sheet' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120', // 5MB
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'signed_document.required' => 'Please upload the signed resolution document.',
            'signed_document.mimes' => 'The signed document must be a PDF file.',
            'signed_document.max' => 'The signed document must not exceed 10MB.',
            'signature_sheet.mimes' => 'The signature sheet must be a PDF, JPG, or PNG file.',
            'signature_sheet.max' => 'The signature sheet must not exceed 5MB.',
        ];
    }
}
