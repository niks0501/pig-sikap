<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('batches.index') }}" class="rounded-xl p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">Breeder Registry</h2>
                <p class="mt-1 text-sm text-gray-500">Maintain inahin records used by pig batches.</p>
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

        <div class="grid gap-6 xl:grid-cols-3">
            <section class="xl:col-span-1">
                <article class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                    <h3 class="text-base font-bold text-gray-900">Register Breeder</h3>
                    <form action="{{ route('breeders.store') }}" method="POST" class="mt-4 space-y-3">
                        @csrf

                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Breeder Code *</span>
                            <input type="text" name="breeder_code" value="{{ old('breeder_code') }}" required class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20" placeholder="e.g. INA-001">
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Name / Tag *</span>
                            <input type="text" name="name_or_tag" value="{{ old('name_or_tag') }}" required class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20" placeholder="e.g. Inahin A">
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Reproductive Status *</span>
                            <select name="reproductive_status" required class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                @foreach ($reproductiveStatuses as $item)
                                    <option value="{{ $item }}" @selected(old('reproductive_status', 'Active') === $item)>{{ $item }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Acquisition Date</span>
                            <input type="date" name="acquisition_date" value="{{ old('acquisition_date') }}" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Expected Farrowing Date</span>
                            <input type="date" name="expected_farrowing_date" value="{{ old('expected_farrowing_date') }}" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Notes</span>
                            <textarea name="notes" rows="3" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">{{ old('notes') }}</textarea>
                        </label>

                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">
                            Save Breeder
                        </button>
                    </form>
                </article>
            </section>

            <section class="xl:col-span-2">
                <article class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4 sm:px-6">
                        <form method="GET" action="{{ route('breeders.create') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            <label class="flex-1">
                                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Search breeders</span>
                                <input type="text" name="search" value="{{ $search }}" placeholder="Code, tag, or status"
                                    class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                            </label>
                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">
                                Search
                            </button>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Code</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Tag Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Acquired</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Expected Farrowing</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white text-sm">
                                @forelse ($breeders as $breeder)
                                    <tr>
                                        <td class="px-4 py-3 font-bold text-gray-900">{{ $breeder->breeder_code }}</td>
                                        <td class="px-4 py-3 text-gray-800">{{ $breeder->name_or_tag }}</td>
                                        <td class="px-4 py-3"><span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">{{ $breeder->reproductive_status }}</span></td>
                                        <td class="px-4 py-3 text-gray-700">{{ $breeder->acquisition_date?->format('M d, Y') ?? '-' }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $breeder->expected_farrowing_date?->format('M d, Y') ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-10 text-center text-sm font-medium text-gray-500">No breeder records found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($breeders->hasPages())
                        <div class="border-t border-gray-200 px-4 py-3">
                            {{ $breeders->links() }}
                        </div>
                    @endif
                </article>
            </section>
        </div>
    </div>
</x-app-layout>