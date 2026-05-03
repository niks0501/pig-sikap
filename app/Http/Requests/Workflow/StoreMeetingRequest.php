<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates meeting creation data – mirrors the paper logbook format.
 */
class StoreMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Secretary, Treasurer, or President may create meetings
        $slug = $this->user()?->role?->slug;

        return in_array($slug, ['secretary', 'treasurer', 'president'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'location' => ['nullable', 'string', 'max:255'],
            'agenda' => ['nullable', 'string', 'max:5000'],
            'minutes_summary' => ['nullable', 'string', 'max:10000'],
            'minutes_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'status' => ['nullable', 'in:draft,confirmed,cancelled'],
            'attendees' => ['nullable', 'array'],
            'attendees.*.user_id' => ['required_with:attendees', 'exists:users,id'],
            'attendees.*.attendance_status' => ['required_with:attendees', 'in:present,absent,excused'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Please enter a meeting title.',
            'date.required' => 'Please select the meeting date.',
            'date.before_or_equal' => 'Meeting date cannot be in the future.',
            'minutes_file.max' => 'The minutes file must be 10 MB or less.',
        ];
    }
}
