<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('health.index') }}" class="rounded-xl p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 leading-tight">Sick & Isolated Cases</h2>
                    <p class="mt-1 text-sm text-gray-500">Open incident records requiring monitoring and follow-up.</p>
                </div>
            </div>
            <a href="{{ route('health.create') }}" class="inline-flex items-center justify-center rounded-xl bg-orange-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition-colors hover:bg-orange-700">
                Record New Incident
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <form method="GET" action="{{ route('health.sick') }}" class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:p-5">
            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Search</span>
                <div class="flex gap-2">
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Cycle code, cause, treatment"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-gray-900 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/20"
                    >
                    <button type="submit" class="rounded-xl bg-orange-600 px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-orange-700">
                        Filter
                    </button>
                </div>
            </label>
        </form>

        <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            @forelse ($incidents as $incident)
                @php
                    $cycle = $incident->cycle;
                    $isIsolated = $incident->incident_type === 'isolated';
                @endphp
                <article class="relative overflow-hidden rounded-3xl border bg-white p-5 shadow-sm sm:p-6 {{ $isIsolated ? 'border-amber-200' : 'border-orange-200' }}">
                    <div class="absolute right-0 top-0 h-full w-2 {{ $isIsolated ? 'bg-amber-500' : 'bg-orange-500' }}"></div>

                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-bold {{ $isIsolated ? 'bg-amber-100 text-amber-800' : 'bg-orange-100 text-orange-800' }}">
                            {{ ucfirst((string) $incident->incident_type) }}
                        </span>
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            {{ optional($incident->date_reported)->format('M d, Y') }}
                        </span>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900">
                        @if ($cycle)
                            <a href="{{ route('health.cycles.show', $cycle) }}" class="hover:text-[#0c6d57]">{{ $cycle->batch_code }}</a>
                        @else
                            Unknown cycle
                        @endif
                    </h3>

                    <div class="mt-3 space-y-2 text-sm text-gray-700">
                        <p><span class="font-bold text-gray-900">Affected:</span> {{ number_format((int) $incident->affected_count) }} pig(s)</p>
                        @if ($incident->suspected_cause)
                            <p><span class="font-bold text-gray-900">Cause:</span> {{ $incident->suspected_cause }}</p>
                        @endif
                        @if ($incident->treatment_given)
                            <p><span class="font-bold text-gray-900">Treatment:</span> {{ $incident->treatment_given }}</p>
                        @endif
                        @if ($incident->remarks)
                            <p class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-600">
                                {{ $incident->remarks }}
                            </p>
                        @endif
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($cycle)
                            <a href="{{ route('cycles.show', $cycle) }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition-colors hover:bg-gray-50">
                                Open Cycle
                            </a>
                            <a href="{{ route('health.cycles.show', $cycle) }}" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57]/10 px-3 py-2 text-xs font-semibold text-[#0c6d57] transition-colors hover:bg-[#0c6d57]/20">
                                Open Health Timeline
                            </a>
                        @endif
                    </div>
                </article>
            @empty
                <p class="rounded-xl border border-dashed border-gray-300 bg-white px-4 py-6 text-sm text-gray-500 lg:col-span-2">
                    No sick or isolated incidents found.
                </p>
            @endforelse
        </section>

        <div>
            {{ $incidents->links() }}
        </div>
    </div>
</x-app-layout>
