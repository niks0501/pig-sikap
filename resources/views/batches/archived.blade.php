<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">Archived Pig Batches</h2>
                <p class="mt-1 text-sm text-gray-500">Completed and closed inventory records.</p>
            </div>
            <a href="{{ route('batches.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Back to Active Registry
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <section class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <form method="GET" action="{{ route('batches.archived') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <label class="flex-1">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Search archived batch</span>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Batch code or breeder"
                        class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                </label>
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">
                    Search
                </button>
            </form>
        </section>

        <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Batch</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Breeder</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Final Count</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Stage / Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Updated</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($batches as $batch)
                            <tr>
                                <td class="px-4 py-3 font-bold text-gray-900">{{ $batch->batch_code }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $batch->breeder?->breeder_code ?? '-' }} {{ $batch->breeder?->name_or_tag ? '- '.$batch->breeder?->name_or_tag : '' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ number_format($batch->current_count) }} / {{ number_format($batch->initial_count) }}</td>
                                <td class="px-4 py-3 text-gray-700">
                                    <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-800">{{ $batch->stage }}</span>
                                    <span class="ml-1 rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-800">{{ $batch->status }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $batch->updated_at?->format('M d, Y h:i A') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('batches.show', $batch) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm font-medium text-gray-500">
                                    No archived batches found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($batches->hasPages())
                <div class="border-t border-gray-200 px-4 py-3">
                    {{ $batches->links() }}
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
