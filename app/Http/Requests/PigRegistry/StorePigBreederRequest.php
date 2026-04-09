<?php

namespace App\Http\Requests\PigRegistry;

use App\Models\PigBreeder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePigBreederRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('president') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'breeder_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('pig_breeders', 'breeder_code')->withoutTrashed(),
            ],
            'name_or_tag' => ['required', 'string', 'max:120'],
            'reproductive_status' => ['required', 'string', Rule::in(PigBreeder::REPRODUCTIVE_STATUSES)],
            'acquisition_date' => ['nullable', 'date'],
            'expected_farrowing_date' => ['nullable', 'date', 'after_or_equal:acquisition_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
