<?php

namespace App\Http\Requests;

use App\Models\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $documentTypeId = $this->input('document_type_id');
        $documentType = null;

        if ($documentTypeId) {
            $documentType = DocumentType::find($documentTypeId);
        }

        $allowedTypes = $documentType?->allowed_file_types ?? ['pdf', 'jpg', 'png'];
        $maxSizeKb = $documentType?->max_size_kb ?? 10240;

        $rules = [
            'document_type_id' => ['required', 'exists:document_types,id'],
            'file' => ['required', 'file', 'max:' . $maxSizeKb],
            'module_type' => ['nullable', 'string'],
            'module_id' => ['nullable', 'integer'],
        ];

        if (!empty($allowedTypes)) {
            $rules['file'][] = 'mimes:' . implode(',', $allowedTypes);
        }

        return $rules;
    }

    public function messages(): array
    {
        $documentTypeId = $this->input('document_type_id');
        $documentType = $documentTypeId ? DocumentType::find($documentTypeId) : null;
        $allowedTypes = $documentType?->allowed_file_types ?? ['pdf', 'jpg', 'png'];
        $maxSizeKb = $documentType?->max_size_kb ?? 10240;

        return [
            'file.max' => 'The file size must not exceed ' . $maxSizeKb . ' KB.',
            'file.mimes' => 'The file must be of type: ' . implode(', ', $allowedTypes) . '.',
        ];
    }
}