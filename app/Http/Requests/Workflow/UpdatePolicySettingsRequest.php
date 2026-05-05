<?php

namespace App\Http\Requests\Workflow;

use App\Models\AssociationPolicySetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePolicySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $knownKeys = AssociationPolicySetting::pluck('key', 'key')->toArray();

        return [
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', Rule::in($knownKeys)],
            'settings.*.value' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'settings.*.key.in' => 'Unknown policy key provided.',
        ];
    }
}