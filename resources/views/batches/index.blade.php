<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">Pig Registry</h2>
                <p class="mt-1 text-sm text-gray-500">Batch-centered inventory for litters, status tracking, and lightweight pig profiles.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('batches.archived') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                    Archived
                </a>
                <a href="{{ route('breeders.create') }}" class="inline-flex items-center justify-center rounded-xl border border-[#0c6d57]/30 bg-[#0c6d57]/5 px-4 py-2.5 text-sm font-semibold text-[#0c6d57] transition hover:bg-[#0c6d57]/10">
                    Breeder Registry
                </a>
                <a href="{{ route('batches.create') }}" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">
                    Create Batch
                </a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
                {{ $errors->first() }}
            </div>
        @endif

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Active Batches</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($summary['active_batches']) }}</p>
                <p class="mt-1 text-xs font-medium text-gray-500">Operational litters in progress</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Piglets</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($summary['total_piglets']) }}</p>
                <p class="mt-1 text-xs font-medium text-gray-500">Stage: Piglet</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Breeders</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($summary['total_breeders']) }}</p>
                <p class="mt-1 text-xs font-medium text-gray-500">Registered inahin records</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Fatteners</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($summary['total_fatteners']) }}</p>
                <p class="mt-1 text-xs font-medium text-gray-500">Stage: Fattening</p>
            </article>
            <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Sick Pigs</p>
                <p class="mt-2 text-3xl font-bold text-amber-900">{{ number_format($summary['total_sick']) }}</p>
                <p class="mt-1 text-xs font-medium text-amber-700">Needs monitoring</p>
            </article>
            <article class="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">Deceased Pigs</p>
                <p class="mt-2 text-3xl font-bold text-rose-900">{{ number_format($summary['total_deceased']) }}</p>
                <p class="mt-1 text-xs font-medium text-rose-700">From pig profile records</p>
            </article>
            <article class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm sm:col-span-2">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Ready For Sale Batches</p>
                <p class="mt-2 text-3xl font-bold text-blue-900">{{ number_format($summary['ready_for_sale_batches']) }}</p>
                <p class="mt-1 text-xs font-medium text-blue-700">Status: Ready for Sale</p>
            </article>
        </section>

        <section class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <form method="GET" action="{{ route('batches.index') }}" class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
                <label class="xl:col-span-2">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Search</span>
                    <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Batch code, breeder, caretaker"
                        class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-800 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                </label>

                <label>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Scope</span>
                    <select name="scope" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-800 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        <option value="all" @selected($filters['scope'] === 'all')>All</option>
                        <option value="active" @selected($filters['scope'] === 'active')>Active</option>
                        <option value="archived" @selected($filters['scope'] === 'archived')>Archived</option>
                    </select>
                </label>

                <label>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Stage</span>
                    <select name="stage" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-800 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        <option value="">All stages</option>
                        @foreach ($stages as $item)
                            <option value="{{ $item }}" @selected($filters['stage'] === $item)>{{ $item }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Status</span>
                    <select name="status" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-800 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $item)
                            <option value="{{ $item }}" @selected($filters['status'] === $item)>{{ $item }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Breeder</span>
                    <select name="breeder" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-800 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        <option value="">All breeders</option>
                        @foreach ($breeders as $breeder)
                            <option value="{{ $breeder->id }}" @selected((string) $filters['breeder'] === (string) $breeder->id)>
                                {{ $breeder->breeder_code }} - {{ $breeder->name_or_tag }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Caretaker</span>
                    <select name="caretaker" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-800 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        <option value="">All caretakers</option>
                        @foreach ($caretakers as $caretaker)
                            <option value="{{ $caretaker->id }}" @selected((string) $filters['caretaker'] === (string) $caretaker->id)>
                                {{ $caretaker->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <div class="flex items-end gap-2 md:col-span-2 xl:col-span-6">
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">
                        Apply Filters
                    </button>
                    <a href="{{ route('batches.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                        Reset
                    </a>
                </div>
            </form>
        </section>

        <div class="grid gap-6 xl:grid-cols-3">
            <section class="xl:col-span-2">
                <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
                    <div class="hidden overflow-x-auto md:block">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.15em] text-gray-500">Batch</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.15em] text-gray-500">Breeder</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.15em] text-gray-500">Birth Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.15em] text-gray-500">Count</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.15em] text-gray-500">Stage / Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-[0.15em] text-gray-500">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($batches as $batch)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 align-top">
                                            <p class="text-sm font-bold text-gray-900">{{ $batch->batch_code }}</p>
                                            <p class="text-xs text-gray-500">Caretaker: {{ $batch->caretaker?->name ?? 'Unassigned' }}</p>
                                        </td>
                                        <td class="px-4 py-3 align-top text-sm text-gray-700">
                                            {{ $batch->breeder?->breeder_code ?? 'No breeder linked' }}
                                            <p class="text-xs text-gray-500">{{ $batch->breeder?->name_or_tag ?? '-' }}</p>
                                        </td>
                                        <td class="px-4 py-3 align-top text-sm text-gray-700">{{ $batch->birth_date->format('M d, Y') }}</td>
                                        <td class="px-4 py-3 align-top text-sm text-gray-700">
                                            <span class="font-bold text-gray-900">{{ number_format($batch->current_count) }}</span>
                                            <span class="text-gray-500"> / {{ number_format($batch->initial_count) }}</span>
                                        </td>
                                        <td class="px-4 py-3 align-top text-xs">
                                            <p><span class="rounded-full bg-blue-100 px-2.5 py-1 font-semibold text-blue-800">{{ $batch->stage }}</span></p>
                                            <p class="mt-2"><span class="rounded-full bg-emerald-100 px-2.5 py-1 font-semibold text-emerald-800">{{ $batch->status }}</span></p>
                                        </td>
                                        <td class="px-4 py-3 text-right align-top">
                                            <a href="{{ route('batches.show', $batch) }}" class="inline-flex items-center justify-center rounded-lg border border-[#0c6d57]/30 bg-[#0c6d57]/5 px-3 py-1.5 text-xs font-semibold text-[#0c6d57] transition hover:bg-[#0c6d57]/10">
                                                Open
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-10 text-center text-sm font-medium text-gray-500">
                                            No batch records found for your selected filters.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="space-y-3 p-4 md:hidden">
                        @forelse ($batches as $batch)
                            <article class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-base font-bold text-gray-900">{{ $batch->batch_code }}</h3>
                                        <p class="text-xs text-gray-500">{{ $batch->breeder?->name_or_tag ?? 'No breeder linked' }}</p>
                                    </div>
                                    <a href="{{ route('batches.show', $batch) }}" class="rounded-lg bg-[#0c6d57]/10 px-3 py-1.5 text-xs font-semibold text-[#0c6d57]">Open</a>
                                </div>
                                <dl class="mt-3 grid grid-cols-2 gap-3 text-xs">
                                    <div>
                                        <dt class="font-semibold uppercase tracking-[0.14em] text-gray-500">Count</dt>
                                        <dd class="mt-1 text-sm font-bold text-gray-900">{{ number_format($batch->current_count) }} / {{ number_format($batch->initial_count) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-semibold uppercase tracking-[0.14em] text-gray-500">Birth Date</dt>
                                        <dd class="mt-1 text-sm font-semibold text-gray-800">{{ $batch->birth_date->format('M d, Y') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-semibold uppercase tracking-[0.14em] text-gray-500">Stage</dt>
                                        <dd class="mt-1"><span class="rounded-full bg-blue-100 px-2.5 py-1 font-semibold text-blue-800">{{ $batch->stage }}</span></dd>
                                    </div>
                                    <div>
                                        <dt class="font-semibold uppercase tracking-[0.14em] text-gray-500">Status</dt>
                                        <dd class="mt-1"><span class="rounded-full bg-emerald-100 px-2.5 py-1 font-semibold text-emerald-800">{{ $batch->status }}</span></dd>
                                    </div>
                                </dl>
                            </article>
                        @empty
                            <p class="rounded-2xl border border-gray-200 bg-white px-4 py-6 text-center text-sm font-medium text-gray-500">
                                No batch records found for your selected filters.
                            </p>
                        @endforelse
                    </div>

                    @if ($batches->hasPages())
                        <div class="border-t border-gray-200 px-4 py-3">
                            {{ $batches->links() }}
                        </div>
                    @endif
                </div>
            </section>

            <section class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                <h3 class="text-base font-bold text-gray-900">Recent Inventory Updates</h3>
                <p class="mt-1 text-xs text-gray-500">Latest status and count changes from Pig Registry actions.</p>

                <div class="mt-4 space-y-3">
                    @forelse ($recentUpdates as $update)
                        <article class="rounded-2xl border border-gray-200 bg-gray-50 p-3">
                            <p class="text-sm font-semibold text-gray-900">{{ $update['batch_code'] ?? 'Batch' }}</p>
                            <p class="mt-1 text-xs text-gray-600">{{ $update['description'] }}</p>
                            <p class="mt-2 text-[11px] font-medium uppercase tracking-[0.14em] text-gray-500">
                                {{ $update['actor'] ?? 'System' }}
                                @if ($update['created_at'])
                                    · {{ $update['created_at']->format('M d, Y h:i A') }}
                                @endif
                            </p>
                        </article>
                    @empty
                        <p class="rounded-2xl border border-dashed border-gray-300 px-3 py-5 text-center text-sm text-gray-500">
                            No updates recorded yet.
                        </p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>