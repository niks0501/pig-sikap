<?php

namespace App\Http\Requests\PigRegistry;

use App\Models\PigCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReopenPigCycleRequest extends FormRequest
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
            'new_stage' => ['required', 'string', Rule::in(array_values(array_diff(PigCycle::STAGES, ['Completed'])))],
            'new_status' => ['required', 'string', Rule::in(array_values(array_diff(PigCycle::STATUSES, PigCycle::ARCHIVED_STATUSES)))],
            'remarks' => ['required', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $cycle = $this->route('cycle');

            if (! $cycle instanceof PigCycle) {
                return;
            }

            if (! $cycle->isArchived()) {
                $validator->errors()->add('cycle', 'Only archived cycles can be reopened.');
            }
        });
    }
}
