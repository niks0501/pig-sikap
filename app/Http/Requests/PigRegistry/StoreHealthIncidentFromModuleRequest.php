<?php

namespace App\Http\Requests\PigRegistry;

use App\Models\CycleHealthIncident;
use App\Models\PigCycle;
use App\Services\PigRegistry\CycleHealthStateProjector;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreHealthIncidentFromModuleRequest extends FormRequest
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
        return [
            'cycle_id' => ['required', 'integer', 'exists:pig_cycles,id'],
            'event_key' => ['required', 'uuid'],
            'pig_id' => ['nullable', 'integer', 'exists:pigs,id'],
            'source_channel' => ['nullable', 'string', 'max:80'],
            'incident_type' => ['required', 'string', Rule::in(CycleHealthIncident::INCIDENT_TYPES)],
            'date_reported' => ['required', 'date'],
            'affected_count' => ['required', 'integer', 'min:1'],
            'suspected_cause' => ['nullable', 'string', 'max:1000'],
            'treatment_given' => ['nullable', 'string', 'max:1000'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'media' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'media_path' => ['nullable', 'string', 'max:2048'],
            'resolution_target' => ['nullable', 'string', Rule::in(CycleHealthIncident::RESOLUTION_TARGETS)],
            'resolved_incident_id' => ['nullable', 'integer', 'exists:cycle_health_incidents,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $cycleId = (int) $this->input('cycle_id', 0);
            $eventKey = (string) $this->input('event_key', '');
            $incidentType = (string) $this->input('incident_type', '');
            $affectedCount = (int) $this->input('affected_count', 0);
            $pigId = (int) $this->input('pig_id', 0);
            $resolutionTarget = CycleHealthIncident::normalizeResolutionTarget($this->input('resolution_target'));
            $resolvedIncidentId = (int) $this->input('resolved_incident_id', 0);

            if ($cycleId < 1) {
                return;
            }

            /** @var PigCycle|null $cycle */
            $cycle = PigCycle::query()->find($cycleId);

            if ($cycle === null) {
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

            if ($cycle->isArchived()) {
                $validator->errors()->add('cycle_id', 'Archived cycles cannot accept new health incidents.');
            }

            $isPigSpecificIncident = CycleHealthIncident::isPigSpecificIncidentType($incidentType);
            $hasPigProfiles = (bool) $cycle->has_pig_profiles;
            $cyclePigCount = (int) $cycle->pigs()->count();
            $requiresPigSelection = $isPigSpecificIncident && $hasPigProfiles && $cyclePigCount > 0;

            if ($requiresPigSelection && $pigId < 1) {
                $validator->errors()->add(
                    'pig_id',
                    'Select a pig profile for isolated, deceased, or recovered incidents when pig profiles exist for this cycle.'
                );
            }

            if ($pigId > 0 && ! $cycle->pigs()->whereKey($pigId)->exists()) {
                $validator->errors()->add('pig_id', 'The selected pig does not belong to the selected cycle.');
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
                        'The selected resolved incident must belong to the selected cycle.'
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
}
