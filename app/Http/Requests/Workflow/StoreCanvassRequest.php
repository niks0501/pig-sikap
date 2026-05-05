<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class StoreCanvassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'canvass_date' => ['required', 'date'],
            'resolution_id' => ['nullable', 'exists:resolutions,id'],
            'meeting_id' => ['nullable', 'exists:meetings,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.specifications' => ['nullable', 'string', 'max:1000'],
            'items.*.category' => ['nullable', 'string', 'max:255'],
            'items.*.supplier_id' => ['nullable', 'exists:suppliers,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit' => ['required', 'string', 'max:50'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ];
    }
}