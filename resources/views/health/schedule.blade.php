<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('health.index') }}" class="rounded-xl p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">Health Schedule</h2>
                <p class="mt-1 text-sm text-gray-500">Focus first on overdue and due-now health activities.</p>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" action="{{ route('health.schedule') }}" class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:p-5 flex-1">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Search</span>
                    <div class="flex gap-2">
                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Cycle code or task"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                        >
                        <input type="hidden" name="view" value="{{ $view }}">
                        <button type="submit" class="rounded-xl bg-[#0c6d57] px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-[#0a5a48]">
                            Filter
                        </button>
                    </div>
                </label>
            </form>

            <div class="flex rounded-xl border border-gray-200 bg-white p-1 shadow-sm" role="group" aria-label="Schedule view toggle">
                <a
                    href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}"
                    class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-bold transition-colors min-h-[44px] {{ $view === 'list' ? 'bg-[#0c6d57] text-white' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}"
                >
                    <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    List
                </a>
                <a
                    href="{{ request()->fullUrlWithQuery(['view' => 'calendar']) }}"
                    class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-bold transition-colors min-h-[44px] {{ $view === 'calendar' ? 'bg-[#0c6d57] text-white' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}"
                >
                    <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Calendar
                </a>
            </div>
        </div>

        @if ($view === 'calendar')
            <div data-vue-component="schedule-calendar" data-props='@json(["tasks" => $calendarTasks])'></div>
        @else
            <section class="space-y-4">
                <h3 class="text-base font-bold text-red-600">Overdue</h3>
                <div class="space-y-3">
                    @forelse ($overdueTasks as $task)
                        @include('health.partials.schedule-task-card', [
                            'task' => $task,
                            'cycle' => $task->cycle,
                            'plannedDate' => optional($task->planned_start_date)->format('M d, Y'),
                            'toneClass' => 'border-red-200 bg-red-50/50',
                        ])
                    @empty
                        <p class="rounded-xl border border-dashed border-gray-300 bg-white px-4 py-4 text-sm text-gray-500">No overdue tasks found.</p>
                    @endforelse
                </div>
            </section>

            <section class="space-y-4">
                <h3 class="text-base font-bold text-amber-700">Due Today</h3>
                <div class="space-y-3">
                    @forelse ($dueTodayTasks as $task)
                        @include('health.partials.schedule-task-card', [
                            'task' => $task,
                            'cycle' => $task->cycle,
                            'plannedDate' => optional($task->planned_start_date)->format('M d, Y'),
                            'toneClass' => 'border-amber-200 bg-amber-50/60',
                        ])
                    @empty
                        <p class="rounded-xl border border-dashed border-gray-300 bg-white px-4 py-4 text-sm text-gray-500">No due-today tasks found.</p>
                    @endforelse
                </div>
            </section>

            <section class="space-y-4">
                <h3 class="text-base font-bold text-[#0c6d57]">Upcoming (Next 14 Days)</h3>
                <div class="space-y-3">
                    @forelse ($upcomingTasks as $task)
                        @include('health.partials.schedule-task-card', [
                            'task' => $task,
                            'cycle' => $task->cycle,
                            'plannedDate' => optional($task->planned_start_date)->format('M d, Y'),
                            'toneClass' => 'border-gray-200 bg-white',
                        ])
                    @empty
                        <p class="rounded-xl border border-dashed border-gray-300 bg-white px-4 py-4 text-sm text-gray-500">No upcoming tasks found.</p>
                    @endforelse
                </div>
            </section>
        @endif
    </div>

    </x-app-layout>
