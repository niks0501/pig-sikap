<x-app-layout>
    @php
        $eventKey = old('event_key', (string) \Illuminate\Support\Str::uuid());
        $formProps = [
            'cycles' => $cycles
                ->map(function ($cycle): array {
                    return [
                        'id' => (int) $cycle->id,
                        'batch_code' => (string) $cycle->batch_code,
                        'date_of_purchase' => optional($cycle->date_of_purchase)->toDateString(),
                        'current_count' => (int) $cycle->current_count,
                        'has_pig_profiles' => (bool) $cycle->has_pig_profiles,
                        'pig_count' => (int) ($cycle->pigs_count ?? $cycle->pigs->count()),
                        'pigs' => $cycle->pigs
                            ->map(fn ($pig): array => [
                                'id' => (int) $pig->id,
                                'pig_no' => (int) $pig->pig_no,
                                'status' => (string) $pig->status,
                            ])
                            ->values()
                            ->all(),
                        'active_health' => [
                            'currently_sick' => (int) data_get($cycle->active_health, 'currently_sick', 0),
                            'currently_isolated' => (int) data_get($cycle->active_health, 'currently_isolated', 0),
                            'currently_affected' => (int) data_get($cycle->active_health, 'currently_affected', 0),
                        ],
                    ];
                })
                ->values()
                ->all(),
            'incidentTypes' => array_values($incidentTypes),
            'pigSpecificIncidentTypes' => \App\Models\CycleHealthIncident::PIG_SPECIFIC_INCIDENT_TYPES,
            'selectedCycleId' => (int) $selectedCycleId,
            'routes' => [
                'store' => route('health.incidents.store'),
                'index' => route('health.index'),
            ],
            'csrfToken' => csrf_token(),
            'eventKey' => $eventKey,
            'oldInput' => [
                'cycle_id' => (string) old('cycle_id', $selectedCycleId),
                'incident_type' => (string) old('incident_type', ''),
                'date_reported' => (string) old('date_reported', now()->toDateString()),
                'affected_count' => (string) old('affected_count', '1'),
                'pig_id' => (string) old('pig_id', ''),
                'resolution_target' => (string) old('resolution_target', ''),
                'media_path' => (string) old('media_path', ''),
                'suspected_cause' => (string) old('suspected_cause', ''),
                'treatment_given' => (string) old('treatment_given', ''),
                'remarks' => (string) old('remarks', ''),
            ],
            'errors' => collect($errors->toArray())
                ->map(fn ($messages): string => (string) ($messages[0] ?? ''))
                ->all(),
        ];
    @endphp

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('health.index') }}" class="rounded-xl p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">Record Health Incident</h2>
                <p class="mt-1 text-sm text-gray-500">Log sick, isolated, deceased, and recovered events at cycle level.</p>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl space-y-4 px-4 py-6 sm:px-6 lg:px-8">
        <div data-vue-component="health-incident-create-form" data-props='@json($formProps)'></div>
    </div>
</x-app-layout>
