<?php

namespace App\Http\Requests\PigRegistry;

use App\Models\CycleHealthIncident;
use App\Models\PigCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCycleHealthIncidentRequest extends FormRequest
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
            'event_key' => ['required', 'uuid'],
            'pig_id' => ['nullable', 'integer', 'exists:pigs,id'],
            'source_channel' => ['nullable', 'string', 'max:80'],
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
            $eventKey = (string) $this->input('event_key', '');
            $incidentType = (string) $this->input('incident_type');
            $affectedCount = (int) $this->input('affected_count', 0);
            $pigId = (int) $this->input('pig_id', 0);
            $cycle = $this->route('cycle');

            if (! $cycle instanceof PigCycle) {
                return;
            }

            $existingIncident = $eventKey !== ''
                ? CycleHealthIncident::query()
                    ->where('batch_id', $cycle->id)
                    ->where('event_key', $eventKey)
                    ->first()
                : null;

            if ($existingIncident !== null) {
                return;
            }

            if ($pigId > 0 && ! $cycle->pigs()->whereKey($pigId)->exists()) {
                $validator->errors()->add('pig_id', 'The selected pig does not belong to this cycle.');
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
