<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('health.index') }}" class="rounded-xl p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 leading-tight">Mortality Records</h2>
                    <p class="mt-1 text-sm text-gray-500">Deceased incidents captured through Health Monitoring with evidence and causes.</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('health.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-50">
                    Back to Health
                </a>
                <a href="{{ route('health.create') }}" class="inline-flex items-center justify-center rounded-xl border border-[#0c6d57]/30 bg-white px-3 py-2 text-sm font-semibold text-[#0c6d57] transition-colors hover:bg-[#0c6d57]/5">
                    Record General Incident
                </a>
                <a href="{{ route('health.mortality.create') }}" class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-3 py-2 text-sm font-semibold text-white transition-colors hover:bg-rose-700">
                    Record Mortality
                </a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <form method="GET" action="{{ route('health.mortality') }}" class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:p-5">
            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Search</span>
                <div class="flex gap-2">
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Cycle, pig no., cause, remarks, reporter"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-gray-900 focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20"
                    >
                    <button type="submit" class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-rose-700">
                        Filter
                    </button>
                </div>
            </label>
        </form>

        <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            @forelse ($incidents as $incident)
                @php
                    $cycle = $incident->cycle;
                    $pig = $incident->pig;
                    $mediaPath = (string) ($incident->media_path ?? '');
                    $mediaUrl = $mediaPath !== '' ? asset('storage/' . $mediaPath) : null;
                    $lowerPath = strtolower($mediaPath);
                    $isVideoMedia = str_ends_with($lowerPath, '.mp4') || str_ends_with($lowerPath, '.mov') || str_ends_with($lowerPath, '.avi');
                @endphp
                <article class="relative overflow-hidden rounded-3xl border border-rose-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="absolute right-0 top-0 h-full w-2 bg-rose-500"></div>

                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <span class="inline-flex rounded-lg bg-rose-100 px-2.5 py-1 text-xs font-bold text-rose-800">
                            Deceased
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
                        @if ($pig)
                            <p><span class="font-bold text-gray-900">Pig:</span> #{{ number_format((int) $pig->pig_no) }}</p>
                        @endif
                        <p><span class="font-bold text-gray-900">Affected:</span> {{ number_format((int) $incident->affected_count) }} pig(s)</p>
                        @if ($incident->suspected_cause)
                            <p><span class="font-bold text-gray-900">Cause:</span> {{ $incident->suspected_cause }}</p>
                        @endif
                        @if ($incident->reportedBy)
                            <p><span class="font-bold text-gray-900">Reported By:</span> {{ $incident->reportedBy->name }}</p>
                        @endif
                        @if ($incident->remarks)
                            <p class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-600">
                                {{ $incident->remarks }}
                            </p>
                        @endif
                    </div>

                    @if ($mediaUrl)
                        <div class="mt-4 rounded-2xl border border-gray-200 bg-gray-50 p-3">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Uploaded Evidence</p>

                            @if ($isVideoMedia)
                                <video controls class="max-h-80 w-full rounded-xl border border-gray-200 bg-black">
                                    <source src="{{ $mediaUrl }}">
                                    Your browser does not support this video format.
                                </video>
                            @else
                                <a href="{{ $mediaUrl }}" target="_blank" rel="noopener noreferrer" class="block overflow-hidden rounded-xl border border-gray-200 bg-white p-2">
                                    <img src="{{ $mediaUrl }}" alt="Mortality evidence image" class="max-h-80 w-full rounded-lg object-contain">
                                </a>
                            @endif
                        </div>
                    @endif

                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($cycle)
                            <a href="{{ route('health.cycles.show', $cycle) }}" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57]/10 px-3 py-2 text-xs font-semibold text-[#0c6d57] transition-colors hover:bg-[#0c6d57]/20">
                                Open Health Timeline
                            </a>
                            <a href="{{ route('cycles.show', $cycle) }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition-colors hover:bg-gray-50">
                                Open Cycle
                            </a>
                        @endif
                    </div>
                </article>
            @empty
                <p class="rounded-xl border border-dashed border-gray-300 bg-white px-4 py-6 text-sm text-gray-500 lg:col-span-2">
                    No mortality incidents found.
                </p>
            @endforelse
        </section>

        <div>
            {{ $incidents->links() }}
        </div>
    </div>
</x-app-layout>
