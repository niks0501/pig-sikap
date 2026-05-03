<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates recording of member approvals (batch sign-off).
 */
class StoreApprovalRequest extends FormRequest
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
            'approvals' => ['required', 'array', 'min:1'],
            'approvals.*.user_id' => ['required', 'exists:users,id'],
            'approvals.*.is_approved' => ['required', 'boolean'],
            'approvals.*.rejection_reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'approvals.required' => 'Please select at least one member.',
            'approvals.min' => 'Please select at least one member.',
        ];
    }
}
