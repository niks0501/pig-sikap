<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Profit Sharing: {{ $cycle->batch_code }}</h2>
            <p class="text-sm text-gray-500">Association rule: 50% caretaker, 25% members, 25% association fund.</p>
        </div>
    </x-slot>

    @php
        $money = fn ($value) => '₱'.number_format((float) $value, 2);
        $net = (float) $profitability['net_profit_or_loss'];
        $hasDistribution = (float) $profitability['distributable_profit'] > 0;
        $isFinalized = $profitability['is_finalized'] ?? false;
        $receivables = (float) ($profitability['receivables'] ?? 0);
    @endphp

    <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between print:hidden">
            <a href="{{ route('profitability.show', $cycle) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Back to Breakdown</a>
            <div class="flex flex-wrap gap-2">
                @if ($isFinalized && $snapshot)
                    <a href="{{ route('profitability.snapshots.show', $snapshot) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">View Snapshot</a>
                    <a href="{{ route('profitability.snapshots.report.preview', $snapshot) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50" target="_blank">Preview Report</a>
                    <a href="{{ route('profitability.snapshots.report.download', $snapshot) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Download Report</a>
                @elseif (! $isFinalized && $profitability['has_sales'])
                    <a href="{{ route('profitability.report.preview', $cycle) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50" target="_blank">Preview Draft Report</a>
                @endif
            </div>
        </div>

        @if ($isFinalized && $snapshot)
            <section class="rounded-2xl border border-green-300 bg-green-50 p-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-bold text-green-900">
                            <span class="inline-flex rounded-full bg-green-200 text-green-800 px-2 py-0.5 text-xs font-bold">Finalized Official Snapshot</span>
                            Version {{ $snapshot->version_number }}
                        </p>
                        <p class="mt-1 text-sm text-green-700">Finalized by {{ $snapshot->finalizedBy?->name ?? 'Unknown user' }} on {{ $snapshot->finalized_at?->format('M d, Y h:i A') }}.</p>
                    </div>
                </div>
                @if ($snapshot->notes)
                    <p class="mt-2 rounded-xl bg-white px-3 py-2 text-sm text-gray-700">{{ $snapshot->notes }}</p>
                @endif
                @if ($dataChanged)
                    <div class="mt-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3">
                        <p class="text-sm font-bold text-amber-900">Data Changed After Finalization</p>
                        <p class="mt-1 text-sm text-amber-800">Expense or sale records have been modified since this snapshot was finalized. Review and re-finalize if needed.</p>
                    </div>
                @endif
            </section>
        @elseif (! $isFinalized)
            <section class="rounded-2xl border border-blue-200 bg-blue-50/70 p-5">
                <p class="text-sm font-bold text-blue-900">
                    <span class="inline-flex rounded-full bg-blue-200 text-blue-800 px-2 py-0.5 text-xs font-bold">Live Computation</span>
                </p>
                <p class="mt-1 text-sm text-gray-600">These values reflect current records and may change until finalized.</p>
            </section>
        @endif

        @if ($receivables > 0)
            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <p class="text-sm font-bold text-amber-800">Pending Collection / Receivables</p>
                <p class="mt-1 text-sm text-amber-700">₱{{ number_format($receivables, 2) }} in sales has not yet been collected. Profitability is based on total recorded sales regardless of collection.</p>
            </section>
        @endif

        <section class="rounded-2xl border {{ $hasDistribution ? 'border-[#0c6d57]/20 bg-[#0c6d57]/5' : 'border-amber-200 bg-amber-50' }} p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.16em] {{ $hasDistribution ? 'text-[#0c6d57]' : 'text-amber-800' }}">Distributable Profit</p>
            <p class="mt-2 text-4xl font-extrabold {{ $hasDistribution ? 'text-[#0a5a48]' : 'text-amber-900' }}">{{ $money($profitability['distributable_profit']) }}</p>
            <p class="mt-2 text-sm {{ $hasDistribution ? 'text-[#0a5a48]' : 'text-amber-900' }}">
                @if ($net < 0)
                    This cycle has a loss of {{ $money(abs($net)) }}. No profit should be distributed. All stakeholder shares remain ₱0.00.
                @elseif (! $hasDistribution)
                    This cycle has no profit to distribute. All shares remain ₱0.00.
                @else
                    Distribution is based only on net profit after all recorded expenses are deducted.
                @endif
            </p>
        </section>

        @if ($net < 0)
            <section class="rounded-2xl border border-rose-200 bg-rose-50 p-5">
                <p class="text-sm font-bold text-rose-800">Loss / No Distributable Profit</p>
                <p class="mt-1 text-sm text-rose-700">All shares remain ₱0.00. The cycle must recover the operating loss before any distribution is available. Document this in a resolution.</p>
            </section>
        @endif

        <section class="grid gap-4 md:grid-cols-3">
            <article class="rounded-2xl border border-[#0c6d57]/20 bg-white p-5 text-center shadow-sm">
                <p class="text-sm font-bold text-[#0c6d57]">Caretaker / Nag-alaga</p>
                <p class="mt-1 text-xs text-gray-500">50% share</p>
                <p class="mt-4 border-t border-gray-100 pt-4 text-3xl font-extrabold {{ (float) $profitability['caretaker_share'] > 0 ? 'text-gray-900' : 'text-gray-400' }}">{{ $money($profitability['caretaker_share']) }}</p>
            </article>
            <article class="rounded-2xl border border-[#0c6d57]/20 bg-white p-5 text-center shadow-sm">
                <p class="text-sm font-bold text-[#0c6d57]">Association Members</p>
                <p class="mt-1 text-xs text-gray-500">25% share</p>
                <p class="mt-4 border-t border-gray-100 pt-4 text-3xl font-extrabold {{ (float) $profitability['member_share'] > 0 ? 'text-gray-900' : 'text-gray-400' }}">{{ $money($profitability['member_share']) }}</p>
            </article>
            <article class="rounded-2xl border border-[#0c6d57]/20 bg-white p-5 text-center shadow-sm">
                <p class="text-sm font-bold text-[#0c6d57]">Association Fund / Samahan</p>
                <p class="mt-1 text-xs text-gray-500">25% share</p>
                <p class="mt-4 border-t border-gray-100 pt-4 text-3xl font-extrabold {{ (float) $profitability['association_share'] > 0 ? 'text-gray-900' : 'text-gray-400' }}">{{ $money($profitability['association_share']) }}</p>
            </article>
        </section>

        <section class="grid gap-6 md:grid-cols-2">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-bold text-gray-900">Financial Summary</h3>
                <dl class="mt-4 space-y-3">
                    <div class="flex justify-between text-sm">
                        <dt class="text-gray-500">Total Sales</dt>
                        <dd class="font-bold text-gray-900">{{ $money($profitability['total_sales']) }}</dd>
                    </div>
                    <div class="flex justify-between text-sm">
                        <dt class="text-gray-500">Total Collected</dt>
                        <dd class="font-bold text-gray-900">{{ $money($profitability['total_collected'] ?? 0) }}</dd>
                    </div>
                    <div class="flex justify-between text-sm">
                        <dt class="text-gray-500">Receivables</dt>
                        <dd class="font-bold text-amber-700">{{ $money($profitability['receivables'] ?? 0) }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-gray-100 pt-3 text-sm font-bold">
                        <dt class="text-gray-900">Total Expenses</dt>
                        <dd class="text-gray-900">{{ $money($profitability['total_expenses']) }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-gray-100 pt-3 text-sm font-bold">
                        <dt class="text-gray-900">Net Profit / Loss</dt>
                        <dd class="{{ $net < 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ $money($net) }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-bold text-gray-900">Distribution Check</h3>
                <p class="mt-4 text-sm text-gray-600">
                    {{ $money($profitability['caretaker_share']) }} + {{ $money($profitability['member_share']) }} + {{ $money($profitability['association_share']) }} = {{ $money($profitability['distributable_profit']) }}
                </p>
                <div class="mt-4 rounded-xl bg-gray-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Total for Verification</p>
                    <p class="mt-2 text-2xl font-extrabold text-gray-900">{{ $money($profitability['distributable_profit']) }}</p>
                </div>
            </div>
        </section>

        @if (isset($snapshotHistory) && $snapshotHistory->count() > 1)
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm print:hidden">
                <h3 class="text-lg font-bold text-gray-900">Snapshot History</h3>
                <p class="mt-1 text-sm text-gray-500">Previous finalized versions of this cycle.</p>
                <div class="mt-4 space-y-2">
                    @foreach ($snapshotHistory as $hist)
                        <div class="flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3 text-sm {{ $hist->is_current ? 'ring-2 ring-green-300' : '' }}">
                            <div>
                                <p class="font-bold text-gray-900">
                                    Version {{ $hist->version_number }}
                                    @if ($hist->is_current)
                                        <span class="ml-2 inline-flex rounded-full bg-green-100 text-green-800 px-2 py-0.5 text-xs font-bold">Current</span>
                                    @else
                                        <span class="ml-2 inline-flex rounded-full bg-gray-200 text-gray-600 px-2 py-0.5 text-xs font-bold">Superseded</span>
                                    @endif
                                </p>
                                <p class="text-xs text-gray-500">Finalized {{ $hist->finalized_at?->format('M d, Y') }} by {{ $hist->finalizedBy?->name ?? 'N/A' }}</p>
                                @if ($hist->re_finalize_reason_code)
                                    <p class="mt-1 text-xs text-gray-500">{{ $hist->reFinalizeReasonLabel() }}</p>
                                @endif
                            </div>
                            <a href="{{ route('profitability.snapshots.show', $hist) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50">View</a>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($canFinalize && isset($isPresident) && $isPresident)
            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm print:hidden">
                @if ($dataChanged && $isFinalized)
                    <h3 class="text-lg font-bold text-amber-900">Re-finalize Profitability Snapshot</h3>
                    <p class="mt-2 text-sm text-amber-800">Data has changed after the last finalization. Creating a new version will preserve the old snapshot and create an updated official record.</p>
                @else
                    <h3 class="text-lg font-bold text-amber-900">Finalize for Reports and Resolutions</h3>
                    <p class="mt-2 text-sm text-amber-900">After finalizing, this snapshot is locked as the official profitability basis for reports and approval documents.</p>
                @endif

                <form method="POST" action="{{ route('profitability.finalize', $cycle) }}" class="mt-4 space-y-3">
                    @csrf
                    @if ($dataChanged)
                        <input type="hidden" name="re_finalize" value="1">
                        <label class="block">
                            <span class="mb-1 block text-sm font-bold text-gray-700">Reason for Re-finalization *</span>
                            <select name="re_finalize_reason_code" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20">
                                <option value="">Select a reason...</option>
                                <option value="corrected_expense" {{ old('re_finalize_reason_code') === 'corrected_expense' ? 'selected' : '' }}>Corrected Expense</option>
                                <option value="corrected_sale" {{ old('re_finalize_reason_code') === 'corrected_sale' ? 'selected' : '' }}>Corrected Sale</option>
                                <option value="late_payment_update" {{ old('re_finalize_reason_code') === 'late_payment_update' ? 'selected' : '' }}>Late Payment Update</option>
                                <option value="missing_receipt_added" {{ old('re_finalize_reason_code') === 'missing_receipt_added' ? 'selected' : '' }}>Missing Receipt Added</option>
                                <option value="wrong_amount_encoded" {{ old('re_finalize_reason_code') === 'wrong_amount_encoded' ? 'selected' : '' }}>Wrong Amount Encoded</option>
                                <option value="cycle_record_correction" {{ old('re_finalize_reason_code') === 'cycle_record_correction' ? 'selected' : '' }}>Cycle Record Correction</option>
                                <option value="other" {{ old('re_finalize_reason_code') === 'other' ? 'selected' : '' }}>Other Reason</option>
                            </select>
                            @error('re_finalize_reason_code')
                                <span class="mt-1 block text-sm font-semibold text-rose-700">{{ $message }}</span>
                            @enderror
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-sm font-bold text-gray-700">Detailed Explanation *</span>
                            <textarea name="re_finalize_reason_notes" rows="3" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20" placeholder="Explain what changed and why re-finalization is needed (e.g., corrected mis-encoded quantity for feed)">{{ old('re_finalize_reason_notes') }}</textarea>
                            @error('re_finalize_reason_notes')
                                <span class="mt-1 block text-sm font-semibold text-rose-700">{{ $message }}</span>
                            @enderror
                        </label>
                    @endif

                    <label class="block">
                        <span class="mb-1 block text-sm font-bold text-gray-700">Finalization Notes (optional)</span>
                        <textarea name="notes" rows="2" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20" placeholder="Example: Approved after reviewing expense and sales records.">{{ old('notes') }}</textarea>
                        @error('notes')
                            <span class="mt-1 block text-sm font-semibold text-rose-700">{{ $message }}</span>
                        @enderror
                    </label>

                    @error('cycle')
                        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">
                            @if (is_array($message))
                                @foreach ($message as $msg)
                                    <p>{{ $msg }}</p>
                                @endforeach
                            @else
                                {{ $message }}
                            @endif
                        </div>
                    @enderror

                    <button type="submit" class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0a5a48] sm:w-auto">
                        {{ $dataChanged ? 'Re-finalize Official Snapshot' : 'Finalize Official Snapshot' }}
                    </button>
                </form>
            </section>
        @elseif (! $snapshot && isset($isPresident) && ! $isPresident)
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm print:hidden">
                <h3 class="text-lg font-bold text-gray-900">Snapshot Not Finalized</h3>
                <p class="mt-2 text-sm text-gray-600">Only the President can finalize profitability after the cycle is completed, sold, or closed.</p>
            </section>
        @endif
    </div>
</x-app-layout>