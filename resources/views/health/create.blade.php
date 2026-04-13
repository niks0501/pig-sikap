<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('health.index') }}" class="rounded-xl p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">Record Health Incident</h2>
                <p class="mt-1 text-sm text-gray-500">Log sick, isolated, or deceased incidents at cycle level.</p>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl space-y-4 px-4 py-6 sm:px-6 lg:px-8">
        <section class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
            <form action="{{ route('health.incidents.store') }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid gap-5 sm:grid-cols-2">
                    <label class="sm:col-span-2">
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Cycle *</span>
                        <select name="cycle_id" required class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                            <option value="" disabled @selected(!old('cycle_id', $selectedCycleId))>Select cycle...</option>
                            @foreach ($cycles as $cycle)
                                <option value="{{ $cycle->id }}" @selected((int) old('cycle_id', $selectedCycleId) === (int) $cycle->id)>
                                    {{ $cycle->batch_code }} • Current {{ number_format((int) $cycle->current_count) }} pigs • Purchased {{ optional($cycle->date_of_purchase)->format('M d, Y') }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Incident Type *</span>
                        <select name="incident_type" required class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                            <option value="" disabled @selected(!old('incident_type'))>Select type...</option>
                            @foreach ($incidentTypes as $incidentType)
                                <option value="{{ $incidentType }}" @selected(old('incident_type') === $incidentType)>
                                    {{ ucfirst($incidentType) }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Date Reported *</span>
                        <input type="date" name="date_reported" required value="{{ old('date_reported', now()->toDateString()) }}" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                    </label>

                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Affected Count *</span>
                        <input type="number" name="affected_count" min="1" required value="{{ old('affected_count', 1) }}" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                    </label>

                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Media Path (optional)</span>
                        <input type="text" name="media_path" value="{{ old('media_path') }}" placeholder="storage/app/public/..." class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                    </label>

                    <label class="sm:col-span-2">
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Suspected Cause</span>
                        <textarea name="suspected_cause" rows="2" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">{{ old('suspected_cause') }}</textarea>
                    </label>

                    <label class="sm:col-span-2">
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Treatment Given</span>
                        <textarea name="treatment_given" rows="2" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">{{ old('treatment_given') }}</textarea>
                    </label>

                    <label class="sm:col-span-2">
                        <span class="mb-1.5 block text-sm font-bold text-gray-700">Remarks</span>
                        <textarea name="remarks" rows="3" placeholder="Physical back markings, isolation notes, observations." class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">{{ old('remarks') }}</textarea>
                    </label>
                </div>

                <div class="flex flex-col gap-3 border-t border-gray-100 pt-4 sm:flex-row-reverse">
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-[#0c6d57] px-6 py-3 text-sm font-bold text-white shadow-sm transition-colors hover:bg-[#0a5a48] sm:w-auto">
                        Save Incident
                    </button>
                    <a href="{{ route('health.index') }}" class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 bg-white px-6 py-3 text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:w-auto">
                        Cancel
                    </a>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
