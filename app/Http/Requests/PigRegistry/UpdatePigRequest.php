<?php

namespace App\Http\Requests\PigRegistry;

use App\Models\Pig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePigRequest extends FormRequest
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
        $cycle = $this->route('cycle') ?? $this->route('batch');
        $pig = $this->route('pig');
        $cycleId = $cycle?->id;

        return [
            'pig_no' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('pigs', 'pig_no')
                    ->ignore($pig?->id)
                    ->where(fn ($query) => $query->where('batch_id', $cycleId)),
            ],
            'ear_mark_type' => ['nullable', 'string', 'max:50'],
            'ear_mark_value' => ['nullable', 'string', 'max:80'],
            'sex' => ['nullable', 'string', Rule::in(Pig::SEX_OPTIONS)],
            'status' => ['required', 'string', Rule::in(Pig::STATUSES), Rule::notIn(['Deceased'])],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.not_in' => 'Use Health Monitoring mortality recording to set a pig as Deceased.',
        ];
    }
}
