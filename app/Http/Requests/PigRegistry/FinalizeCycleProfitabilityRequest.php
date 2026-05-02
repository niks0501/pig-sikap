<?php

namespace App\Http\Requests\PigRegistry;

use Illuminate\Foundation\Http\FormRequest;

class FinalizeCycleProfitabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('president') ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
