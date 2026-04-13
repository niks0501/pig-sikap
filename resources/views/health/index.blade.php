<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">Health & Treatments</h2>
                <p class="mt-1 text-sm text-gray-500">Track cycle health tasks, mark actions, and monitor incident load.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('health.schedule') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-700 shadow-sm transition-colors hover:bg-gray-50">
                    View Schedule
                </a>
                <a href="{{ route('health.create') }}" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2.5 text-sm font-bold text-white shadow-sm transition-colors hover:bg-[#0a5a48]">
                    Record Incident
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $activeTab = (string) ($filters['tab'] ?? 'all');
        $search = trim((string) ($filters['search'] ?? ''));
        $tabQuery = fn (string $tab): array => array_filter([
            'tab' => $tab,
            'search' => $search !== '' ? $search : null,
        ]);

        $activeTabLabel = match ($activeTab) {
            'upcoming' => 'Upcoming',
            'overdue' => 'Overdue',
            'completed' => 'Completed',
            'sick' => 'Sick / Isolated',
            default => 'All Records',
        };

        $hasNeedsAction = isset($needsAction['overdue'], $needsAction['due_today'], $needsAction['upcoming_soon'])
            && ($needsAction['overdue']->isNotEmpty() || $needsAction['due_today']->isNotEmpty() || $needsAction['upcoming_soon']->isNotEmpty());

        $needsActionGroups = [
            [
                'key' => 'overdue',
                'title' => 'Overdue Tasks',
                'hint' => 'Act first',
                'cardClass' => 'border-red-200 bg-red-50/70',
                'pillClass' => 'bg-red-100 text-red-700',
                'buttonClass' => 'bg-red-600 hover:bg-red-700',
            ],
            [
                'key' => 'due_today',
                'title' => 'Due Today',
                'hint' => 'Address today',
                'cardClass' => 'border-amber-200 bg-amber-50/70',
                'pillClass' => 'bg-amber-100 text-amber-700',
                'buttonClass' => 'bg-amber-600 hover:bg-amber-700',
            ],
            [
                'key' => 'upcoming_soon',
                'title' => 'Due Soon',
                'hint' => 'Plan ahead',
                'cardClass' => 'border-blue-200 bg-blue-50/70',
                'pillClass' => 'bg-blue-100 text-blue-700',
                'buttonClass' => 'bg-blue-600 hover:bg-blue-700',
            ],
        ];
    @endphp

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <section class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <a
                href="{{ route('health.index', $tabQuery('upcoming')) }}"
                class="rounded-2xl border bg-white p-4 shadow-sm transition-colors {{ $activeTab === 'upcoming' ? 'border-[#0c6d57] ring-2 ring-[#0c6d57]/20' : 'border-gray-100 hover:border-gray-300' }}"
            >
                <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Upcoming</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format((int) ($summary['upcoming'] ?? 0)) }}</p>
            </a>
            <a
                href="{{ route('health.index', $tabQuery('overdue')) }}"
                class="rounded-2xl border bg-white p-4 shadow-sm transition-colors {{ $activeTab === 'overdue' ? 'border-red-500 ring-2 ring-red-500/20' : 'border-red-100 hover:border-red-300' }}"
            >
                <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Overdue</p>
                <p class="mt-1 text-2xl font-bold text-red-600">{{ number_format((int) ($summary['overdue'] ?? 0)) }}</p>
            </a>
            <a
                href="{{ route('health.index', $tabQuery('completed')) }}"
                class="rounded-2xl border bg-white p-4 shadow-sm transition-colors {{ $activeTab === 'completed' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-emerald-100 hover:border-emerald-300' }}"
            >
                <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Completed</p>
                <p class="mt-1 text-2xl font-bold text-emerald-700">{{ number_format((int) ($summary['completed'] ?? 0)) }}</p>
            </a>
            <a
                href="{{ route('health.index', $tabQuery('sick')) }}"
                class="rounded-2xl border bg-white p-4 shadow-sm transition-colors {{ $activeTab === 'sick' ? 'border-orange-500 ring-2 ring-orange-500/20' : 'border-orange-100 hover:border-orange-300' }}"
            >
                <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Sick / Isolated</p>
                <p class="mt-1 text-2xl font-bold text-orange-600">{{ number_format((int) ($summary['sick_cases'] ?? 0)) }}</p>
            </a>
        </section>

        <section class="flex flex-wrap items-center gap-2">
            <span class="inline-flex rounded-full bg-[#0c6d57]/10 px-3 py-1 text-xs font-bold text-[#0c6d57]">
                Viewing: {{ $activeTabLabel }}
            </span>
            @if ($activeTab !== 'all' || $search !== '')
                <a href="{{ route('health.index') }}" class="inline-flex rounded-full border border-gray-300 bg-white px-3 py-1 text-xs font-bold text-gray-700 transition-colors hover:bg-gray-50">
                    Reset to All Records
                </a>
            @endif
            <a href="{{ route('health.sick') }}" class="inline-flex rounded-full border border-orange-200 bg-orange-50 px-3 py-1 text-xs font-bold text-orange-700 transition-colors hover:bg-orange-100">
                Open Full Sick Log
            </a>
        </section>

        <section class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:p-5">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h3 class="text-base font-bold text-gray-900">Needs Action</h3>
                    <p class="mt-1 text-sm text-gray-500">Priority order: overdue first, then due today, then upcoming soon.</p>
                </div>
                <a href="{{ route('health.schedule') }}" class="inline-flex w-full items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:w-auto">
                    Open Full Schedule
                </a>
            </div>

            @if ($hasNeedsAction)
                <div class="grid gap-3 lg:grid-cols-3">
                    @foreach ($needsActionGroups as $group)
                        @php
                            $groupTasks = $needsAction[$group['key']] ?? collect();
                        @endphp
                        @if ($groupTasks->isNotEmpty())
                            <article class="rounded-xl border p-3 {{ $group['cardClass'] }}">
                                <div class="flex items-center justify-between gap-2">
                                    <h4 class="text-sm font-bold text-gray-900">{{ $group['title'] }}</h4>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold {{ $group['pillClass'] }}">{{ $group['hint'] }}</span>
                                </div>

                                <ul class="mt-3 space-y-2">
                                    @foreach ($groupTasks as $task)
                                        @php
                                            $cycle = $task->cycle;
                                        @endphp
                                        <li class="rounded-lg border border-white/80 bg-white p-2.5">
                                            <div class="flex flex-col gap-2">
                                                <div>
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $cycle?->batch_code ?? 'Unknown cycle' }}</p>
                                                    <p class="text-sm font-bold text-gray-900">{{ $task->task_name }}</p>
                                                    <p class="text-xs text-gray-600">Planned {{ optional($task->planned_start_date)->format('M d, Y') }}</p>
                                                </div>

                                                @if ($cycle)
                                                    <form action="{{ route('health.cycles.tasks.update', [$cycle, $task]) }}" method="POST" class="flex gap-2">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="action" value="complete_all">
                                                        <input type="hidden" name="actual_date" value="{{ now()->toDateString() }}">
                                                        <button type="submit" class="inline-flex flex-1 items-center justify-center rounded-lg px-3 py-2 text-xs font-bold text-white {{ $group['buttonClass'] }}">
                                                            Complete
                                                        </button>
                                                        <a href="{{ route('health.cycles.show', $cycle) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50">
                                                            Open
                                                        </a>
                                                    </form>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </article>
                        @endif
                    @endforeach
                </div>
            @else
                <p class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-3 py-4 text-sm font-medium text-gray-600">
                    No urgent tasks at the moment. You are all caught up.
                </p>
            @endif
        </section>

        <section class="rounded-2xl border border-gray-100 bg-white shadow-sm">
            <form method="GET" action="{{ route('health.index') }}" class="grid gap-3 border-b border-gray-100 p-4 sm:grid-cols-12 sm:p-5">
                <input type="hidden" name="tab" value="{{ $activeTab }}">

                <label class="sm:col-span-8">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Search</span>
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Cycle code, task name, status"
                        class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                    >
                </label>

                <div class="flex items-end gap-2 sm:col-span-4">
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-[#0c6d57] px-3 py-2.5 text-sm font-bold text-white transition-colors hover:bg-[#0a5a48]">
                        Apply Search
                    </button>
                    @if ($search !== '')
                        <a href="{{ route('health.index', ['tab' => $activeTab]) }}" class="inline-flex w-full items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50">
                            Clear
                        </a>
                    @endif
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Cycle</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Task</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Planned</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Coverage</th>
                            <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-gray-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($tasks as $task)
                            @php
                                $cycle = $task->cycle;
                                $status = (string) $task->status;
                                $isTerminal = in_array($status, $terminalStatuses, true);
                                $plannedStartDate = $task->planned_start_date;
                                $hasPlannedStartDate = $plannedStartDate instanceof \Carbon\CarbonInterface;
                                $isOralMedication = (string) $task->task_type === 'oral_medication_period';

                                $reminderLabel = null;
                                $reminderClass = '';

                                if (! $isTerminal && $hasPlannedStartDate && ! $isOralMedication) {
                                    if ($plannedStartDate->lt(today())) {
                                        $reminderLabel = 'Overdue';
                                        $reminderClass = 'bg-red-100 text-red-700';
                                    } elseif ($plannedStartDate->isToday()) {
                                        $reminderLabel = 'Due Today';
                                        $reminderClass = 'bg-amber-100 text-amber-700';
                                    } elseif ($plannedStartDate->lte(today()->copy()->addDays(3))) {
                                        $reminderLabel = 'Due Soon';
                                        $reminderClass = 'bg-blue-100 text-blue-700';
                                    }
                                }

                                if (
                                    $reminderLabel === null
                                    && ! $isTerminal
                                    && (bool) $task->is_optional
                                    && $hasPlannedStartDate
                                    && $plannedStartDate->isCurrentMonth()
                                ) {
                                    $reminderLabel = 'Optional This Month';
                                    $reminderClass = 'bg-gray-200 text-gray-700';
                                }

                                $statusClass = match ($status) {
                                    'completed' => 'bg-emerald-100 text-emerald-800',
                                    'partially_completed' => 'bg-amber-100 text-amber-800',
                                    'overdue' => 'bg-red-100 text-red-800',
                                    'skipped', 'not_applicable' => 'bg-gray-200 text-gray-700',
                                    'rescheduled' => 'bg-blue-100 text-blue-800',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                            @endphp
                            <tr>
                                <td class="px-4 py-3 align-top">
                                    @if ($cycle)
                                        <a href="{{ route('health.cycles.show', $cycle) }}" class="text-sm font-bold text-gray-900 hover:text-[#0c6d57]">
                                            {{ $cycle->batch_code }}
                                        </a>
                                    @else
                                        <span class="text-sm text-gray-400">Unknown</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <p class="text-sm font-bold text-gray-900">{{ $task->task_name }}</p>
                                    <p class="text-xs text-gray-500">{{ str_replace('_', ' ', (string) $task->task_type) }}</p>
                                    @if ($reminderLabel)
                                        <span class="mt-1 inline-flex rounded-lg px-2.5 py-1 text-[11px] font-bold {{ $reminderClass }}">
                                            {{ $reminderLabel }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top text-sm text-gray-700">
                                    <p>{{ optional($task->planned_start_date)->format('M d, Y') }}</p>
                                    @if ($task->planned_end_date)
                                        <p class="text-xs text-gray-500">to {{ optional($task->planned_end_date)->format('M d, Y') }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-bold {{ $statusClass }}">
                                        {{ $task->formatted_status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-top text-sm text-gray-700">
                                    {{ number_format((int) $task->completed_count) }} / {{ number_format((int) $task->target_count) }}
                                </td>
                                <td class="px-4 py-3 text-right align-top">
                                    @if ($cycle)
                                        <div class="inline-flex flex-col items-end gap-1.5">
                                            <a href="{{ route('health.cycles.show', $cycle) }}" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-50">
                                                Open Timeline
                                            </a>

                                            @if (! $isTerminal)
                                                <form action="{{ route('health.cycles.tasks.update', [$cycle, $task]) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="action" value="complete_all">
                                                    <input type="hidden" name="actual_date" value="{{ now()->toDateString() }}">
                                                    <button type="submit" class="rounded-lg bg-[#0c6d57]/10 px-3 py-1.5 text-xs font-bold text-[#0c6d57] hover:bg-[#0c6d57]/20">
                                                        Mark Complete
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs font-semibold text-gray-400">No action</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">
                                    No health tasks found for the selected view.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-100 px-4 py-3 sm:px-5">
                {{ $tasks->links() }}
            </div>
        </section>
    </div>
</x-app-layout>
