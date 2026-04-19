<?php

namespace App\Http\Requests\PigRegistry;

use App\Models\CycleHealthIncident;
use App\Models\Pig;
use App\Models\PigCycle;
use App\Services\PigRegistry\CycleHealthStateProjector;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class StoreCycleHealthIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('president') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $incidentType = CycleHealthIncident::normalizeIncidentType($this->input('incident_type'));
        $resolutionTarget = $this->input('resolution_target');

        if (is_string($resolutionTarget)) {
            $resolutionTarget = strtolower(trim($resolutionTarget));
            $resolutionTarget = $resolutionTarget === '' ? null : $resolutionTarget;
        }

        $this->merge([
            'incident_type' => $incidentType,
            'resolution_target' => $resolutionTarget,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isDeceased = fn (): bool => (string) $this->input('incident_type') === CycleHealthIncident::INCIDENT_TYPE_DECEASED;

        return [
            'event_key' => ['required', 'uuid'],
            'pig_id' => ['nullable', 'integer', 'exists:pigs,id'],
            'source_channel' => ['nullable', 'string', 'max:80'],
            'incident_type' => ['required', 'string', Rule::in(CycleHealthIncident::INCIDENT_TYPES)],
            'date_reported' => ['required', 'date'],
            'affected_count' => ['required', 'integer', 'min:1'],
            'suspected_cause' => [Rule::requiredIf($isDeceased), 'nullable', 'string', 'max:1000'],
            'treatment_given' => ['nullable', 'string', 'max:1000'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'media' => [
                Rule::requiredIf($isDeceased),
                'nullable',
                File::types(['jpg', 'jpeg', 'png', 'webp', 'mp4', 'mov', 'avi'])->max(25 * 1024),
            ],
            'media_path' => ['nullable', 'string', 'max:2048'],
            'resolution_target' => ['nullable', 'string', Rule::in(CycleHealthIncident::RESOLUTION_TARGETS)],
            'resolved_incident_id' => ['nullable', 'integer', 'exists:cycle_health_incidents,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $eventKey = (string) $this->input('event_key', '');
            $incidentType = (string) $this->input('incident_type', '');
            $affectedCount = (int) $this->input('affected_count', 0);
            $pigId = (int) $this->input('pig_id', 0);
            $resolutionTarget = CycleHealthIncident::normalizeResolutionTarget($this->input('resolution_target'));
            $resolvedIncidentId = (int) $this->input('resolved_incident_id', 0);
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

            $isPigSpecificIncident = CycleHealthIncident::isPigSpecificIncidentType($incidentType);
            $hasPigProfiles = (bool) $cycle->has_pig_profiles;
            $cyclePigCount = (int) $cycle->pigs()->count();
            $requiresPigSelection = $isPigSpecificIncident && $hasPigProfiles && $cyclePigCount > 0;
            $selectedPig = $pigId > 0
                ? $cycle->pigs()->whereKey($pigId)->first()
                : null;

            if ($requiresPigSelection && $pigId < 1) {
                $validator->errors()->add(
                    'pig_id',
                    'Select a pig profile for isolated, deceased, or recovered incidents when pig profiles exist for this cycle.'
                );
            }

            if ($pigId > 0 && $selectedPig === null) {
                $validator->errors()->add('pig_id', 'The selected pig does not belong to this cycle.');
            }

            if (
                $incidentType === CycleHealthIncident::INCIDENT_TYPE_DECEASED
                && $selectedPig !== null
                && ! Pig::statusCountsTowardBatch((string) $selectedPig->status)
            ) {
                $validator->errors()->add(
                    'pig_id',
                    'The selected pig is already out of active count and cannot be recorded as deceased again.'
                );
            }

            if ($isPigSpecificIncident && $pigId > 0 && $affectedCount !== 1) {
                $validator->errors()->add(
                    'affected_count',
                    'Pig-specific incidents linked to a pig profile must affect exactly 1 pig.'
                );
            }

            if ($incidentType === 'deceased' && $affectedCount > (int) $cycle->current_count) {
                $validator->errors()->add(
                    'affected_count',
                    'Deceased count cannot be greater than the cycle current count.'
                );
            }

            if (CycleHealthIncident::requiresResolutionTarget($incidentType) && $resolutionTarget === null) {
                $validator->errors()->add(
                    'resolution_target',
                    'Recovered incidents must specify whether they resolve sick or isolated cases.'
                );
            }

            if (
                $resolutionTarget !== null
                && ! CycleHealthIncident::isResolutionIncidentType($incidentType)
            ) {
                $validator->errors()->add(
                    'resolution_target',
                    'Resolution target is only allowed for recovered or deceased incidents.'
                );
            }

            if (
                $incidentType === CycleHealthIncident::INCIDENT_TYPE_RECOVERED
                || ($incidentType === CycleHealthIncident::INCIDENT_TYPE_DECEASED && $resolutionTarget !== null)
            ) {
                $unresolved = app(CycleHealthStateProjector::class)->unresolvedCountsForCycle($cycle);

                $unresolvedCap = (int) ($unresolved[$resolutionTarget ?? ''] ?? 0);

                if ($affectedCount > $unresolvedCap) {
                    $validator->errors()->add(
                        'affected_count',
                        "Affected count exceeds unresolved {$resolutionTarget} cases ({$unresolvedCap})."
                    );
                }
            }

            if ($resolvedIncidentId > 0) {
                $resolvedIncident = CycleHealthIncident::query()->find($resolvedIncidentId);

                if ($resolvedIncident === null || (int) $resolvedIncident->batch_id !== (int) $cycle->id) {
                    $validator->errors()->add(
                        'resolved_incident_id',
                        'The selected resolved incident must belong to this cycle.'
                    );
                } elseif ($resolutionTarget !== null) {
                    $resolvedIncidentType = CycleHealthIncident::normalizeIncidentType((string) $resolvedIncident->incident_type);

                    if ($resolvedIncidentType !== $resolutionTarget) {
                        $validator->errors()->add(
                            'resolved_incident_id',
                            'The selected resolved incident type does not match the resolution target.'
                        );
                    }
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'suspected_cause.required' => 'Suspected cause is required for deceased incidents.',
            'media.required' => 'Upload photo or video evidence for deceased incidents.',
        ];
    }
}
