<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentUploadStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['approved', 'rejected', 'needs_resubmission'])],
            'review_comment' => ['required_if:status,rejected,needs_resubmission', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'review_comment.required_if' => 'Please provide a remark when rejecting or requesting resubmission.',
        ];
    }
}