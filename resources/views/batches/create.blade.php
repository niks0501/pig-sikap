<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('batches.index') }}" class="rounded-xl p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">Create Pig Batch</h2>
                <p class="mt-1 text-sm text-gray-500">Register one litter/group as the main inventory record.</p>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="p-6 sm:p-8">
                @if ($errors->any())
                    <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('batches.store') }}" method="POST" class="space-y-7">
                    @csrf

                    <div class="grid gap-5 sm:grid-cols-2">
                        <label>
                            <span class="mb-1 block text-sm font-semibold text-gray-700">Batch Code *</span>
                            <input type="text" name="batch_code" value="{{ old('batch_code', $batchCode) }}" required
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        </label>

                        <label>
                            <span class="mb-1 block text-sm font-semibold text-gray-700">Birth Date *</span>
                            <input type="date" name="birth_date" value="{{ old('birth_date') }}" required
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                        </label>

                        <label>
                            <span class="mb-1 block text-sm font-semibold text-gray-700">Initial Count *</span>
                            <input type="number" name="initial_count" min="1" value="{{ old('initial_count') }}" required
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                                placeholder="e.g. 10">
                        </label>

                        <label>
                            <span class="mb-1 block text-sm font-semibold text-gray-700">Cycle Number</span>
                            <input type="number" name="cycle_number" min="1" value="{{ old('cycle_number') }}"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                                placeholder="e.g. 5">
                        </label>

                        <label>
                            <span class="mb-1 block text-sm font-semibold text-gray-700">Breeder / Inahin</span>
                            <select name="breeder_id" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                <option value="">No linked breeder</option>
                                @foreach ($breeders as $breeder)
                                    <option value="{{ $breeder->id }}" @selected((string) old('breeder_id') === (string) $breeder->id)>
                                        {{ $breeder->breeder_code }} - {{ $breeder->name_or_tag }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label>
                            <span class="mb-1 block text-sm font-semibold text-gray-700">Caretaker</span>
                            <select name="caretaker_user_id" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                <option value="">Unassigned</option>
                                @foreach ($caretakers as $caretaker)
                                    <option value="{{ $caretaker->id }}" @selected((string) old('caretaker_user_id') === (string) $caretaker->id)>
                                        {{ $caretaker->name }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label>
                            <span class="mb-1 block text-sm font-semibold text-gray-700">Stage *</span>
                            <select name="stage" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                @foreach ($stages as $stage)
                                    <option value="{{ $stage }}" @selected(old('stage', 'Piglet') === $stage)>{{ $stage }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label>
                            <span class="mb-1 block text-sm font-semibold text-gray-700">Status *</span>
                            <select name="status" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected(old('status', 'Active') === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="sm:col-span-2">
                            <span class="mb-1 block text-sm font-semibold text-gray-700">Average Weight (kg)</span>
                            <input type="number" name="average_weight" step="0.01" min="0" value="{{ old('average_weight') }}"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20"
                                placeholder="Optional">
                        </label>

                        <label class="sm:col-span-2">
                            <span class="mb-1 block text-sm font-semibold text-gray-700">Notes</span>
                            <textarea name="notes" rows="4" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 shadow-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20" placeholder="Optional context for this batch">{{ old('notes') }}</textarea>
                        </label>
                    </div>

                    <label class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                        <input type="checkbox" name="has_pig_profiles" value="1" @checked(old('has_pig_profiles')) class="mt-0.5 h-4 w-4 rounded border-gray-300 text-[#0c6d57] focus:ring-[#0c6d57]/40">
                        <span>
                            <span class="block text-sm font-semibold text-emerald-900">Auto-generate pig profiles</span>
                            <span class="mt-1 block text-xs text-emerald-700">If checked, the system creates pig no. rows from 1 to the initial count. You can edit exceptions later.</span>
                        </span>
                    </label>

                    <div class="flex flex-col gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end">
                        <a href="{{ route('batches.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">
                            Save Batch
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>