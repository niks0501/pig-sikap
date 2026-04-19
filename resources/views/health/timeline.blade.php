<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('health.index') }}" class="rounded-xl p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-2xl font-bold text-gray-900 leading-tight">{{ $cycle->batch_code }} Health Timeline</h2>
                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800">
                            {{ $cycle->status }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Chronological task and incident history for this cycle.</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('health.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-50">
                    Back to Health
                </a>
                <a href="{{ route('health.create', ['cycle_id' => $cycle->id]) }}" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-3 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#0a5a48]">
                    Record Incident
                </a>
                <a href="{{ route('health.mortality.create', ['cycle_id' => $cycle->id, 'affected_count' => 1]) }}" class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-3 py-2 text-sm font-semibold text-white transition-colors hover:bg-rose-700">
                    Record Mortality
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $timelineProps = [
            'cycle' => [
                'batch_code' => $cycle->batch_code,
                'stage' => $cycle->stage,
                'status' => $cycle->status,
                'date_of_purchase' => optional($cycle->date_of_purchase)->toDateString(),
                'days_since_acquisition' => $cycle->days_since_acquisition,
                'current_count' => (int) $cycle->current_count,
                'health_template' => [
                    'name' => $cycle->healthTemplate?->name,
                    'code' => $cycle->healthTemplate?->code,
                ],
            ],
            'healthSummary' => $healthSummary,
            'oralMedicationTask' => $oralMedicationTask ? [
                'task_name' => (string) $oralMedicationTask->task_name,
                'planned_start_date' => optional($oralMedicationTask->planned_start_date)->toDateString(),
                'planned_end_date' => optional($oralMedicationTask->planned_end_date)->toDateString(),
            ] : null,
            'timelineItems' => $timelineItems->map(function (array $item) use ($cycle): array {
                $timelineDate = $item['timeline_date'] ?? null;
                $timelineDateValue = $timelineDate instanceof \Carbon\CarbonInterface
                    ? $timelineDate->toDateString()
                    : (is_string($timelineDate) && $timelineDate !== '' ? $timelineDate : null);

                if (($item['kind'] ?? null) === 'task') {
                    /** @var \App\Models\CycleHealthTask $task */
                    $task = $item['task'];

                    return [
                        'kind' => 'task',
                        'id' => (int) $task->id,
                        'timeline_date' => $timelineDateValue,
                        'timeline_date_label' => (string) ($item['timeline_date_label'] ?? 'Timeline Date'),
                        'task' => [
                            'id' => (int) $task->id,
                            'task_name' => (string) $task->task_name,
                            'task_type' => (string) $task->task_type,
                            'task_type_label' => str((string) $task->task_type)->replace('_', ' ')->title()->toString(),
                            'status' => (string) $task->status,
                            'formatted_status' => (string) $task->formatted_status,
                            'is_optional' => (bool) $task->is_optional,
                            'is_terminal' => in_array((string) $task->status, \App\Models\CycleHealthTask::TERMINAL_STATUSES, true),
                            'is_oral_medication' => (string) $task->task_type === 'oral_medication_period',
                            'target_count' => (int) $task->target_count,
                            'completed_count' => (int) $task->completed_count,
                            'planned_start_date' => optional($task->planned_start_date)->toDateString(),
                            'planned_end_date' => optional($task->planned_end_date)->toDateString(),
                            'follow_up_date' => optional($task->follow_up_date)->toDateString(),
                            'actual_date' => optional($task->actual_date)->toDateString(),
                            'remarks' => $task->remarks,
                            'update_url' => route('health.cycles.tasks.update', [$cycle, $task]),
                        ],
                    ];
                }

                /** @var \App\Models\CycleHealthIncident $incident */
                $incident = $item['incident'];
                $normalizedIncidentType = \App\Models\CycleHealthIncident::normalizeIncidentType((string) $incident->incident_type);
                $isResolutionEvent = \App\Models\CycleHealthIncident::isResolutionIncidentType($normalizedIncidentType);

                return [
                    'kind' => 'incident',
                    'id' => (int) $incident->id,
                    'timeline_date' => $timelineDateValue,
                    'timeline_date_label' => (string) ($item['timeline_date_label'] ?? 'Timeline Date'),
                    'incident' => [
                        'id' => (int) $incident->id,
                        'incident_type' => $normalizedIncidentType,
                        'incident_type_label' => str($normalizedIncidentType)->replace('_', ' ')->title()->toString(),
                        'affected_count' => (int) $incident->affected_count,
                        'suspected_cause' => $incident->suspected_cause,
                        'treatment_given' => $incident->treatment_given,
                        'remarks' => $incident->remarks,
                        'pig' => $incident->pig
                            ? [
                                'id' => (int) $incident->pig->id,
                                'pig_no' => (int) $incident->pig->pig_no,
                                'status' => (string) $incident->pig->status,
                            ]
                            : null,
                        'reported_by_name' => $incident->reportedBy?->name,
                        'media_path' => $incident->media_path,
                        'media_url' => $incident->media_path
                            ? asset('storage/'.$incident->media_path)
                            : null,
                        'resolution_target' => $incident->resolution_target,
                        'resolved_incident_id' => $incident->resolved_incident_id,
                        'is_resolution_event' => $isResolutionEvent,
                        'is_active_case_event' => in_array($normalizedIncidentType, ['sick', 'isolated'], true),
                    ],
                ];
            })->values(),
            'routes' => [
                'healthIndex' => route('health.index'),
                'cycleShow' => route('cycles.show', $cycle),
                'recordIncident' => route('health.create', ['cycle_id' => $cycle->id]),
                'recordMortality' => route('health.mortality.create', ['cycle_id' => $cycle->id, 'affected_count' => 1]),
            ],
            'csrfToken' => csrf_token(),
            'todayDate' => now()->toDateString(),
        ];
    @endphp

    <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div data-vue-component="cycle-health-panel" data-props='@json($timelineProps)'></div>
    </div>
</x-app-layout>
