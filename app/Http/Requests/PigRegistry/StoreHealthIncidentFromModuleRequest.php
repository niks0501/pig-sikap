<?php

namespace App\Http\Requests\PigRegistry;

use App\Models\CycleHealthIncident;
use App\Models\PigCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreHealthIncidentFromModuleRequest extends FormRequest
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
            'cycle_id' => ['required', 'integer', 'exists:pig_cycles,id'],
            'incident_type' => ['required', 'string', Rule::in(CycleHealthIncident::INCIDENT_TYPES)],
            'date_reported' => ['required', 'date'],
            'affected_count' => ['required', 'integer', 'min:1'],
            'suspected_cause' => ['nullable', 'string', 'max:1000'],
            'treatment_given' => ['nullable', 'string', 'max:1000'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'media_path' => ['nullable', 'string', 'max:2048'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $cycleId = (int) $this->input('cycle_id', 0);
            $incidentType = (string) $this->input('incident_type', '');
            $affectedCount = (int) $this->input('affected_count', 0);

            if ($cycleId < 1) {
                return;
            }

            /** @var PigCycle|null $cycle */
            $cycle = PigCycle::query()->find($cycleId);

            if ($cycle === null) {
                return;
            }

            if ($cycle->isArchived()) {
                $validator->errors()->add('cycle_id', 'Archived cycles cannot accept new health incidents.');
            }

            if ($incidentType === 'deceased' && $affectedCount > (int) $cycle->current_count) {
                $validator->errors()->add(
                    'affected_count',
                    'Deceased count cannot be greater than the cycle current count.'
                );
            }
        });
    }
}
