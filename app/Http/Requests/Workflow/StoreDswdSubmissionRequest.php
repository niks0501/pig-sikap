<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates DSWD submission data.
 */
class StoreDswdSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $slug = $this->user()?->role?->slug;

        return in_array($slug, ['secretary', 'treasurer', 'president'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:not_submitted,submitted,approved,returned'],
            'submission_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Please select a DSWD status.',
            'submission_file.max' => 'The file must be 10 MB or less.',
        ];
    }
}
