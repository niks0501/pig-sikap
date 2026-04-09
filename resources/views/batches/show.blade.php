<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-4">
                <a href="{{ route('batches.index') }}" class="rounded-xl p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 leading-tight">{{ $batch->batch_code }}</h2>
                    <p class="mt-1 text-sm text-gray-500">Registered {{ $batch->created_at?->format('M d, Y h:i A') }}</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-800">{{ $batch->stage }}</span>
                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">{{ $batch->status }}</span>
                        @if ($batch->isArchived())
                            <span class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-700">Archived</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('batches.pigs.index', $batch) }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                    Manage Pig Profiles
                </a>
                @unless ($batch->isArchived())
                    <a href="{{ route('batches.edit', $batch) }}" class="inline-flex items-center justify-center rounded-xl border border-[#0c6d57]/30 bg-[#0c6d57]/5 px-4 py-2.5 text-sm font-semibold text-[#0c6d57] transition hover:bg-[#0c6d57]/10">
                        Edit Batch
                    </a>
                    <form method="POST" action="{{ route('batches.archive', $batch) }}" onsubmit="return confirm('Archive this batch? Operational editing will be restricted.');">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-gray-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-900">
                            Archive / Close
                        </button>
                    </form>
                @endunless
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
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Current Count</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($batch->current_count) }}</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Initial Count</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($batch->initial_count) }}</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Pig Profiles</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($batch->pigs->count()) }}</p>
                <p class="mt-1 text-xs font-medium text-gray-500">{{ $batch->has_pig_profiles ? 'Enabled' : 'Not enabled' }}</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Last Reviewed</p>
                <p class="mt-2 text-sm font-bold text-gray-900">{{ $batch->last_reviewed_at?->format('M d, Y h:i A') ?? 'Not yet reviewed' }}</p>
            </article>
        </section>

        <div class="grid gap-6 xl:grid-cols-3">
            <section class="space-y-6 xl:col-span-2">
                <article class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                    <h3 class="text-base font-bold text-gray-900">Batch Information</h3>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="font-semibold text-gray-500">Breeder / Inahin</dt>
                            <dd class="mt-1 text-gray-800">{{ $batch->breeder?->breeder_code ?? 'No linked breeder' }} {{ $batch->breeder?->name_or_tag ? '- '.$batch->breeder?->name_or_tag : '' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-500">Caretaker</dt>
                            <dd class="mt-1 text-gray-800">{{ $batch->caretaker?->name ?? 'Unassigned' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-500">Birth Date</dt>
                            <dd class="mt-1 text-gray-800">{{ $batch->birth_date->format('M d, Y') }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-500">Cycle Number</dt>
                            <dd class="mt-1 text-gray-800">{{ $batch->cycle_number ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-500">Average Weight</dt>
                            <dd class="mt-1 text-gray-800">{{ $batch->average_weight ? number_format((float) $batch->average_weight, 2).' kg' : '-' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-500">Profiles Enabled</dt>
                            <dd class="mt-1 text-gray-800">{{ $batch->has_pig_profiles ? 'Yes' : 'No' }}</dd>
                        </div>
                    </dl>
                    <div class="mt-4 rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700">
                        {{ $batch->notes ?: 'No notes for this batch yet.' }}
                    </div>
                </article>

                <div class="grid gap-6 lg:grid-cols-2">
                    <article class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                        <h3 class="text-base font-bold text-gray-900">Adjust Count</h3>
                        @if ($batch->isArchived())
                            <p class="mt-3 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-medium text-gray-600">
                                Batch is archived. Reopen status first before adjusting count.
                            </p>
                        @else
                            <form action="{{ route('batches.adjustments.store', $batch) }}" method="POST" class="mt-4 space-y-3">
                                @csrf
                                <label class="block">
                                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Type</span>
                                    <select name="adjustment_type" required class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                        @foreach ($adjustmentTypes as $type)
                                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="block">
                                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Quantity Change</span>
                                    <input type="number" name="quantity_change" required value="{{ old('quantity_change') }}" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20" placeholder="Use + or - for correction">
                                </label>

                                <label class="block">
                                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Resulting Count (optional)</span>
                                    <input type="number" min="0" name="quantity_after" value="{{ old('quantity_after') }}" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                </label>

                                <label class="block">
                                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Reason</span>
                                    <select name="reason" required class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                        @foreach ($adjustmentReasons as $reason)
                                            <option value="{{ $reason }}" @selected(old('reason') === $reason)>{{ ucfirst($reason) }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="block">
                                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Remarks</span>
                                    <textarea name="remarks" rows="2" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">{{ old('remarks') }}</textarea>
                                </label>

                                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">
                                    Save Adjustment
                                </button>
                            </form>
                        @endif
                    </article>

                    <article class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                        <h3 class="text-base font-bold text-gray-900">Update Stage / Status</h3>
                        <form action="{{ route('batches.status.store', $batch) }}" method="POST" class="mt-4 space-y-3">
                            @csrf
                            <label class="block">
                                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">New Stage</span>
                                <select name="new_stage" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                    <option value="">Keep {{ $batch->stage }}</option>
                                    @foreach ($stages as $stage)
                                        <option value="{{ $stage }}" @selected(old('new_stage') === $stage)>{{ $stage }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="block">
                                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">New Status</span>
                                <select name="new_status" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                    <option value="">Keep {{ $batch->status }}</option>
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status }}" @selected(old('new_status') === $status)>{{ $status }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="block">
                                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Remarks</span>
                                <textarea name="remarks" rows="2" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">{{ old('remarks') }}</textarea>
                            </label>

                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">
                                Save Status Update
                            </button>
                        </form>
                    </article>
                </div>

                <article class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="text-base font-bold text-gray-900">Pig Profiles</h3>
                        <a href="{{ route('batches.pigs.index', $batch) }}" class="text-sm font-semibold text-[#0c6d57] hover:text-[#0a5a48]">Open dedicated profile manager</a>
                    </div>

                    @unless ($batch->isArchived())
                        <form action="{{ route('batches.pigs.store', $batch) }}" method="POST" class="mt-4 grid gap-3 rounded-2xl border border-gray-200 bg-gray-50 p-4 md:grid-cols-2">
                            @csrf
                            <label>
                                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Pig No.</span>
                                <input type="number" name="pig_no" min="1" required class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                            </label>
                            <label>
                                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Sex</span>
                                <select name="sex" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                    <option value="">Not set</option>
                                    @foreach ($sexOptions as $sex)
                                        <option value="{{ $sex }}">{{ $sex }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>
                                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Ear Mark Type</span>
                                <input type="text" name="ear_mark_type" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20" placeholder="e.g. Left cut">
                            </label>
                            <label>
                                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Ear Mark Value</span>
                                <input type="text" name="ear_mark_value" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20" placeholder="e.g. L-2">
                            </label>
                            <label>
                                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Status</span>
                                <select name="status" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                    @foreach ($pigStatuses as $pigStatus)
                                        <option value="{{ $pigStatus }}" @selected($pigStatus === 'Active')>{{ $pigStatus }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>
                                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Remarks</span>
                                <input type="text" name="remarks" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20" placeholder="Optional">
                            </label>
                            <div class="md:col-span-2">
                                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">
                                    Add Pig Profile
                                </button>
                            </div>
                        </form>
                    @endunless

                    <div class="mt-4 overflow-x-auto rounded-2xl border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Pig #</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Ear Mark</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Sex</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Status</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Remarks</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($batch->pigs as $pig)
                                    <tr>
                                        <td class="px-3 py-2 font-semibold text-gray-900">{{ $pig->pig_no }}</td>
                                        <td class="px-3 py-2 text-gray-700">{{ trim(($pig->ear_mark_type ?? '').' '.($pig->ear_mark_value ?? '')) ?: '-' }}</td>
                                        <td class="px-3 py-2 text-gray-700">{{ $pig->sex ?? '-' }}</td>
                                        <td class="px-3 py-2 text-gray-700">{{ $pig->status }}</td>
                                        <td class="px-3 py-2 text-gray-700">{{ $pig->remarks ?: '-' }}</td>
                                        <td class="px-3 py-2 text-right">
                                            @unless ($batch->isArchived())
                                                <form action="{{ route('batches.pigs.update', [$batch, $pig]) }}" method="POST" class="inline-flex gap-2">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="pig_no" value="{{ $pig->pig_no }}">
                                                    <input type="hidden" name="ear_mark_type" value="{{ $pig->ear_mark_type }}">
                                                    <input type="hidden" name="ear_mark_value" value="{{ $pig->ear_mark_value }}">
                                                    <input type="hidden" name="sex" value="{{ $pig->sex }}">
                                                    <input type="hidden" name="status" value="{{ $pig->status }}">
                                                    <input type="hidden" name="remarks" value="{{ $pig->remarks }}">
                                                    <button type="submit" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                                        Keep As-Is
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-xs font-medium text-gray-500">Locked</span>
                                            @endunless
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-8 text-center text-sm font-medium text-gray-500">
                                            No pig profiles yet. Create a profile above or use the profile manager.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>
            </section>

            <aside class="space-y-6">
                <article class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                    <h3 class="text-base font-bold text-gray-900">Adjustment History</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($batch->adjustments->sortByDesc('created_at') as $adjustment)
                            <div class="rounded-2xl border border-gray-200 bg-gray-50 px-3 py-3 text-sm">
                                <p class="font-semibold text-gray-900">
                                    {{ $adjustment->quantity_before }} -> {{ $adjustment->quantity_after }}
                                    <span class="text-xs uppercase tracking-[0.14em] text-gray-500">({{ $adjustment->adjustment_type }})</span>
                                </p>
                                <p class="mt-1 text-xs text-gray-600">Reason: {{ $adjustment->reason }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $adjustment->createdBy?->name ?? 'System' }} · {{ $adjustment->created_at?->format('M d, Y h:i A') }}</p>
                            </div>
                        @empty
                            <p class="rounded-2xl border border-dashed border-gray-300 px-3 py-5 text-center text-sm text-gray-500">
                                No count adjustments yet.
                            </p>
                        @endforelse
                    </div>
                </article>

                <article class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                    <h3 class="text-base font-bold text-gray-900">Status History</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($batch->statusHistories->sortByDesc('created_at') as $history)
                            <div class="rounded-2xl border border-gray-200 bg-gray-50 px-3 py-3 text-sm">
                                <p class="font-semibold text-gray-900">{{ $history->new_stage }} / {{ $history->new_status }}</p>
                                <p class="mt-1 text-xs text-gray-600">From: {{ $history->old_stage ?: '-' }} / {{ $history->old_status ?: '-' }}</p>
                                @if ($history->remarks)
                                    <p class="mt-1 text-xs text-gray-600">{{ $history->remarks }}</p>
                                @endif
                                <p class="mt-1 text-xs text-gray-500">{{ $history->changedBy?->name ?? 'System' }} · {{ $history->created_at?->format('M d, Y h:i A') }}</p>
                            </div>
                        @empty
                            <p class="rounded-2xl border border-dashed border-gray-300 px-3 py-5 text-center text-sm text-gray-500">
                                No status updates yet.
                            </p>
                        @endforelse
                    </div>
                </article>
            </aside>
        </div>
    </div>
</x-app-layout>