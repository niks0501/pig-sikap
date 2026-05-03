<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates resolution creation – auto-filled from a meeting.
 */
class StoreResolutionRequest extends FormRequest
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
            'meeting_id' => ['required', 'exists:meetings,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'resolution_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'approval_deadline' => ['nullable', 'date', 'after:today'],
            'line_items' => ['nullable', 'array'],
            'line_items.*.category' => ['required_with:line_items', 'string', 'max:255'],
            'line_items.*.description' => ['required_with:line_items', 'string', 'max:255'],
            'line_items.*.quantity' => ['required_with:line_items', 'numeric', 'min:0.01'],
            'line_items.*.unit' => ['required_with:line_items', 'string', 'max:50'],
            'line_items.*.unit_cost' => ['required_with:line_items', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'meeting_id.required' => 'Please select a meeting for this resolution.',
            'title.required' => 'Please enter a resolution title.',
            'line_items.*.quantity.min' => 'Quantity must be at least 0.01.',
        ];
    }
}
