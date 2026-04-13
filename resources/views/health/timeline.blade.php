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
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <section class="relative overflow-hidden rounded-3xl bg-[#0c6d57] p-6 text-white shadow-md">
            <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-[#86d4c1]">Purchase Date</p>
                    <p class="mt-1 text-lg font-bold">{{ optional($cycle->date_of_purchase)->format('M d, Y') }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-[#86d4c1]">Days Since Acquisition</p>
                    <p class="mt-1 text-lg font-bold">{{ $cycle->days_since_acquisition ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-[#86d4c1]">Current Count</p>
                    <p class="mt-1 text-lg font-bold">{{ number_format((int) $cycle->current_count) }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-[#86d4c1]">Overdue Tasks</p>
                    <p class="mt-1 text-lg font-bold">{{ number_format((int) ($healthSummary['counts']['overdue'] ?? 0)) }}</p>
                </div>
                <div>
                    <a href="{{ route('cycles.show', $cycle) }}" class="inline-flex w-full items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-[#0c6d57] transition-colors hover:bg-gray-50">
                        Open Cycle Detail
                    </a>
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2">
            <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">Post-Purchase Health Plan</p>
                <p class="mt-2 text-lg font-bold text-gray-900">
                    {{ $cycle->healthTemplate?->name ?? 'No assigned template' }}
                </p>
                @if ($cycle->healthTemplate?->code)
                    <p class="mt-1 text-sm text-gray-600">Template Code: {{ $cycle->healthTemplate->code }}</p>
                @endif
            </article>

            <article class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">Oral Medication Period</p>
                @if ($oralMedicationTask)
                    <p class="mt-2 text-lg font-bold text-emerald-900">{{ $oralMedicationTask->task_name }}</p>
                    <p class="mt-1 text-sm text-emerald-800">
                        {{ optional($oralMedicationTask->planned_start_date)->format('M d, Y') }}
                        @if ($oralMedicationTask->planned_end_date)
                            to {{ optional($oralMedicationTask->planned_end_date)->format('M d, Y') }}
                        @endif
                    </p>
                @else
                    <p class="mt-2 text-sm font-semibold text-emerald-900">No oral medication period task configured for this cycle.</p>
                @endif
            </article>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Due Today</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format((int) ($healthSummary['counts']['due_today'] ?? 0)) }}</p>
            </article>
            <article class="rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-red-700">Overdue</p>
                <p class="mt-2 text-2xl font-bold text-red-900">{{ number_format((int) ($healthSummary['counts']['overdue'] ?? 0)) }}</p>
            </article>
            <article class="rounded-xl border border-blue-200 bg-blue-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Upcoming</p>
                <p class="mt-2 text-2xl font-bold text-blue-900">{{ number_format((int) ($healthSummary['counts']['upcoming'] ?? 0)) }}</p>
            </article>
            <article class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Incidents</p>
                <p class="mt-2 text-2xl font-bold text-amber-900">{{ number_format((int) ($healthSummary['counts']['incidents'] ?? 0)) }}</p>
            </article>
            <article class="rounded-xl border border-rose-200 bg-rose-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">Mortality</p>
                <p class="mt-2 text-2xl font-bold text-rose-900">{{ number_format((int) ($healthSummary['counts']['mortality'] ?? 0)) }}</p>
            </article>
        </section>

        <section class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:p-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Cycle Timeline</h3>
                    <p class="mt-1 text-sm text-gray-500">A single chronological view of scheduled tasks, treatment actions, and incidents.</p>
                </div>
                <a href="{{ route('health.create', ['cycle_id' => $cycle->id]) }}" class="inline-flex items-center justify-center rounded-xl border border-[#0c6d57]/30 bg-[#0c6d57]/5 px-3 py-2 text-xs font-bold text-[#0c6d57] hover:bg-[#0c6d57]/10">
                    Record Incident
                </a>
            </div>

            <div class="mt-5 space-y-4">
                @forelse ($timelineItems as $item)
                    @php
                        $timelineDate = $item['timeline_date'] ?? null;
                        $timelineDateText = $timelineDate instanceof \Carbon\CarbonInterface
                            ? $timelineDate->format('M d, Y')
                            : '-';
                    @endphp
                    @if (($item['kind'] ?? null) === 'task')
                        @php
                            /** @var \App\Models\CycleHealthTask $task */
                            $task = $item['task'];
                            $status = (string) $task->status;
                            $statusClass = match ($status) {
                                'completed' => 'bg-emerald-100 text-emerald-800',
                                'partially_completed' => 'bg-amber-100 text-amber-800',
                                'overdue' => 'bg-red-100 text-red-800',
                                'rescheduled' => 'bg-blue-100 text-blue-800',
                                'skipped', 'not_applicable' => 'bg-gray-200 text-gray-700',
                                default => 'bg-gray-100 text-gray-700',
                            };
                            $isTerminal = in_array($status, \App\Models\CycleHealthTask::TERMINAL_STATUSES, true);
                            $taskTypeLabel = str((string) $task->task_type)->replace('_', ' ')->title()->toString();
                            $isOralMedication = (string) $task->task_type === 'oral_medication_period';
                        @endphp

                        <article class="relative rounded-2xl border p-4 shadow-sm sm:p-5 {{ $isOralMedication ? 'border-emerald-200 bg-emerald-50/50' : 'border-gray-200 bg-gray-50' }}">
                            <span class="absolute left-3 top-5 h-2.5 w-2.5 rounded-full {{ $isOralMedication ? 'bg-emerald-500' : 'bg-[#0c6d57]' }}"></span>

                            <div class="pl-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="text-base font-bold text-gray-900">{{ $task->task_name }}</h4>
                                        <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-bold {{ $statusClass }}">
                                            {{ $task->formatted_status }}
                                        </span>
                                        @if ($isOralMedication)
                                            <span class="inline-flex rounded-lg bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">
                                                Oral Medication
                                            </span>
                                        @endif
                                    </div>

                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        {{ $item['timeline_date_label'] ?? 'Timeline Date' }}: {{ $timelineDateText }}
                                    </span>
                                </div>

                                <p class="mt-1 text-sm text-gray-600">
                                    {{ $taskTypeLabel }}
                                    @if ($task->planned_start_date)
                                        • Planned {{ optional($task->planned_start_date)->format('M d, Y') }}
                                    @endif
                                    @if ($task->planned_end_date)
                                        to {{ optional($task->planned_end_date)->format('M d, Y') }}
                                    @endif
                                    @if ($task->follow_up_date)
                                        • Follow-up {{ optional($task->follow_up_date)->format('M d, Y') }}
                                    @endif
                                </p>

                                <p class="mt-1 text-sm text-gray-700">
                                    Coverage {{ number_format((int) $task->completed_count) }} / {{ number_format((int) $task->target_count) }}
                                </p>

                                @if (! $cycle->isArchived() && ! $isTerminal)
                                    <form action="{{ route('health.cycles.tasks.update', [$cycle, $task]) }}" method="POST" class="mt-3 space-y-3">
                                        @csrf
                                        @method('PATCH')

                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <label>
                                                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Completed Count</span>
                                                <input
                                                    type="number"
                                                    name="completed_count"
                                                    min="0"
                                                    max="{{ (int) $task->target_count }}"
                                                    placeholder="0 - {{ (int) $task->target_count }}"
                                                    class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                                                >
                                            </label>

                                            <label>
                                                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Actual Date</span>
                                                <input
                                                    type="date"
                                                    name="actual_date"
                                                    value="{{ now()->toDateString() }}"
                                                    class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                                                >
                                            </label>

                                            <label>
                                                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Follow-up Date</span>
                                                <input
                                                    type="date"
                                                    name="follow_up_date"
                                                    class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                                                >
                                            </label>

                                            <label>
                                                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Reschedule Planned Date</span>
                                                <input
                                                    type="date"
                                                    name="planned_start_date"
                                                    class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                                                >
                                            </label>
                                        </div>

                                        <label>
                                            <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Remarks</span>
                                            <textarea
                                                name="remarks"
                                                rows="2"
                                                class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                                            ></textarea>
                                        </label>

                                        <div class="flex flex-wrap gap-2">
                                            <button type="submit" name="action" value="complete_all" class="rounded-lg bg-[#0c6d57] px-3 py-1.5 text-xs font-bold text-white hover:bg-[#0a5a48]">
                                                Complete All
                                            </button>
                                            <button type="submit" name="action" value="partial" class="rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-bold text-white hover:bg-amber-600">
                                                Partial
                                            </button>
                                            <button type="submit" name="action" value="reschedule" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-blue-700">
                                                Reschedule
                                            </button>
                                            @if ($task->is_optional)
                                                <button type="submit" name="action" value="skip" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-50">
                                                    Skip
                                                </button>
                                                <button type="submit" name="action" value="not_applicable" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-50">
                                                    Not Applicable
                                                </button>
                                            @endif
                                        </div>
                                    </form>
                                @else
                                    <p class="mt-3 rounded-lg border border-dashed border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-500">
                                        Task is terminal or cycle is archived.
                                    </p>
                                @endif

                                @if ($task->remarks)
                                    <p class="mt-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-600">
                                        {{ $task->remarks }}
                                    </p>
                                @endif
                            </div>
                        </article>
                    @else
                        @php
                            /** @var \App\Models\CycleHealthIncident $incident */
                            $incident = $item['incident'];
                            $incidentType = (string) $incident->incident_type;
                            $incidentTypeLabel = str($incidentType)->title()->toString();
                            $incidentToneClass = match ($incidentType) {
                                'deceased' => 'border-rose-200 bg-rose-50/60',
                                'isolated' => 'border-amber-200 bg-amber-50/60',
                                default => 'border-orange-200 bg-orange-50/60',
                            };
                        @endphp

                        <article class="relative rounded-2xl border p-4 shadow-sm sm:p-5 {{ $incidentToneClass }}">
                            <span class="absolute left-3 top-5 h-2.5 w-2.5 rounded-full {{ $incidentType === 'deceased' ? 'bg-rose-500' : 'bg-orange-500' }}"></span>

                            <div class="pl-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="text-base font-bold text-gray-900">{{ $incidentTypeLabel }} Incident</h4>
                                        <span class="inline-flex rounded-lg bg-white px-2.5 py-1 text-xs font-bold text-gray-700">
                                            {{ number_format((int) $incident->affected_count) }} pig(s) affected
                                        </span>
                                    </div>

                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        {{ $item['timeline_date_label'] ?? 'Timeline Date' }}: {{ $timelineDateText }}
                                    </span>
                                </div>

                                @if ($incident->suspected_cause)
                                    <p class="mt-1 text-sm text-gray-700"><span class="font-semibold text-gray-900">Cause:</span> {{ $incident->suspected_cause }}</p>
                                @endif

                                @if ($incident->treatment_given)
                                    <p class="mt-1 text-sm text-gray-700"><span class="font-semibold text-gray-900">Treatment:</span> {{ $incident->treatment_given }}</p>
                                @endif

                                @if ($incident->remarks)
                                    <p class="mt-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-600">{{ $incident->remarks }}</p>
                                @endif
                            </div>
                        </article>
                    @endif
                @empty
                    <p class="rounded-xl border border-dashed border-gray-300 px-3 py-5 text-sm text-gray-500">No timeline records available for this cycle yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
