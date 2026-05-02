<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Snapshot #{{ $snapshot->snapshot_number }} v{{ $snapshot->version_number }}</h2>
            <p class="text-sm text-gray-500">{{ $cycle->batch_code }} — Finalized Official Profitability Record</p>
        </div>
    </x-slot>

    @php
        $money = fn ($value) => '₱'.number_format((float) $value, 2);
        $net = (float) $profitability['net_profit_or_loss'];
    @endphp

    <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between print:hidden">
            <a href="{{ route('profitability.show', $cycle) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Back to Profitability</a>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('profitability.sharing', $cycle) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">View Sharing</a>
                <a href="{{ route('profitability.snapshots.report.preview', $snapshot) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50" target="_blank">Preview Report</a>
                <a href="{{ route('profitability.snapshots.report.download', $snapshot) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">Download Report</a>
            </div>
        </div>

        <section class="rounded-2xl border border-green-300 bg-green-50 p-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-bold text-green-900">
                        <span class="inline-flex rounded-full {{ $snapshot->is_current ? 'bg-green-200 text-green-800' : 'bg-gray-200 text-gray-600' }} px-2 py-0.5 text-xs font-bold">
                            {{ $snapshot->is_current ? 'Finalized Official Snapshot' : 'Superseded — Historical Version' }}
                        </span>
                    </p>
                    <p class="mt-1 text-sm text-green-700">
                        Version {{ $snapshot->version_number }}
                        — Finalized by {{ $snapshot->finalizedBy?->name ?? 'Unknown user' }}
                        on {{ $snapshot->finalized_at?->format('M d, Y h:i A') }}
                    </p>
                    @if ($snapshot->notes)
                        <p class="mt-2 rounded-xl bg-white px-3 py-2 text-sm text-gray-700">{{ $snapshot->notes }}</p>
                    @endif
                    @if ($snapshot->re_finalize_reason_code)
                        <div class="mt-2 rounded-xl bg-white px-3 py-2 text-sm">
                            <span class="font-semibold text-gray-700">Re-finalization reason:</span>
                            <span class="text-gray-600">{{ $snapshot->reFinalizeReasonLabel() }}</span>
                            @if ($snapshot->re_finalize_reason_notes)
                                <p class="mt-1 text-gray-600">{{ $snapshot->re_finalize_reason_notes }}</p>
                            @endif
                        </div>
                    @endif
                </div>
                @if (! $snapshot->is_current)
                    <span class="inline-flex rounded-full bg-gray-200 text-gray-600 px-3 py-1 text-xs font-bold">Not Current</span>
                @endif
            </div>
            @if ($snapshot->supersedes_snapshot_id && $snapshot->supersedes)
                <div class="mt-3 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    Supersedes version {{ $snapshot->supersedes->version_number }} (finalized {{ $snapshot->supersedes->finalized_at?->format('M d, Y') }}).
                </div>
            @endif
            @if ($snapshot->supersededBy->isNotEmpty())
                <div class="mt-3 rounded-xl border border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Superseded by version {{ $snapshot->supersededBy->first()->version_number }}.
                </div>
            @endif
        </section>

        <section class="rounded-2xl border {{ $net < 0 ? 'border-rose-200 bg-rose-50' : 'border-[#0c6d57]/20 bg-[#0c6d57]/5' }} p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full {{ $net < 0 ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800' }} px-3 py-1 text-xs font-bold">{{ $net < 0 ? 'Loss' : 'Profit' }}</span>
                    <h3 class="mt-4 text-sm font-semibold uppercase tracking-[0.16em] {{ $net < 0 ? 'text-rose-700' : 'text-[#0c6d57]' }}">Net Profit / Loss</h3>
                    <p class="mt-2 text-4xl font-extrabold {{ $net < 0 ? 'text-rose-800' : 'text-[#0a5a48]' }}">{{ $money($net) }}</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 lg:w-96">
                    <div class="rounded-xl bg-white/80 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Total Sales</p>
                        <p class="mt-1 text-xl font-bold text-gray-900">{{ $money($profitability['total_sales']) }}</p>
                    </div>
                    <div class="rounded-xl bg-white/80 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Total Expenses</p>
                        <p class="mt-1 text-xl font-bold text-gray-900">{{ $money($profitability['total_expenses']) }}</p>
                    </div>
                </div>
            </div>
        </section>

        @if ($net < 0)
            <section class="rounded-2xl border border-rose-200 bg-rose-50 p-5">
                <p class="text-sm font-bold text-rose-800">Loss / No Distributable Profit</p>
                <p class="mt-1 text-sm text-rose-700">All stakeholder shares are ₱0.00 because this cycle recorded a net loss.</p>
            </section>
        @endif

        <section class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-4 text-center shadow-sm">
                <p class="text-sm font-bold text-[#0c6d57]">Caretaker</p>
                <p class="mt-1 text-xs text-gray-500">50% share</p>
                <p class="mt-3 text-2xl font-extrabold text-gray-900">{{ $money($profitability['caretaker_share']) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 text-center shadow-sm">
                <p class="text-sm font-bold text-[#0c6d57]">Members</p>
                <p class="mt-1 text-xs text-gray-500">25% share</p>
                <p class="mt-3 text-2xl font-extrabold text-gray-900">{{ $money($profitability['member_share']) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 text-center shadow-sm">
                <p class="text-sm font-bold text-[#0c6d57]">Association Fund</p>
                <p class="mt-1 text-xs text-gray-500">25% share</p>
                <p class="mt-3 text-2xl font-extrabold text-gray-900">{{ $money($profitability['association_share']) }}</p>
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-bold text-gray-900">Sales</h3>
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
                </dl>
                @if (! empty($profitability['sales_breakdown_rows']))
                    <div class="mt-4 space-y-2">
                        @foreach ($profitability['sales_breakdown_rows'] as $row)
                            <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm">
                                <p class="text-gray-700">{{ $row['label'] }}</p>
                                <p class="font-bold text-gray-900">{{ $money($row['total']) }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>

            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-bold text-gray-900">Expenses</h3>
                <div class="mt-4 space-y-2">
                    @foreach ($profitability['expense_breakdown_rows'] as $row)
                        <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm">
                            <p class="text-gray-700">{{ $row['label'] }}</p>
                            <p class="font-bold text-gray-900">{{ $money($row['total']) }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 flex justify-between border-t border-gray-100 pt-4 text-sm font-bold text-gray-900">
                    <span>Total Expenses</span>
                    <span>{{ $money($profitability['total_expenses']) }}</span>
                </div>
            </article>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900">Snapshot Metadata</h3>
            <dl class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Snapshot Number</dt>
                    <dd class="mt-1 text-sm font-bold text-gray-900">{{ $snapshot->snapshot_number }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Version</dt>
                    <dd class="mt-1 text-sm font-bold text-gray-900">{{ $snapshot->version_number }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Cycle Code</dt>
                    <dd class="mt-1 text-sm font-bold text-gray-900">{{ $cycle->batch_code }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Caretaker</dt>
                    <dd class="mt-1 text-sm font-bold text-gray-900">{{ $cycle->caretaker?->name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Cycle Status</dt>
                    <dd class="mt-1 text-sm font-bold text-gray-900">{{ $cycle->status }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Finalized At</dt>
                    <dd class="mt-1 text-sm font-bold text-gray-900">{{ $snapshot->finalized_at?->format('M d, Y h:i A') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Finalized By</dt>
                    <dd class="mt-1 text-sm font-bold text-gray-900">{{ $snapshot->finalizedBy?->name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Source Hash</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-500 text-xs break-all">{{ $snapshot->source_hash ? substr($snapshot->source_hash, 0, 16).'...' : 'N/A' }}</dd>
                </div>
            </dl>
        </section>

        @if ($history->count() > 1)
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm print:hidden">
                <h3 class="text-lg font-bold text-gray-900">Version History</h3>
                <p class="mt-1 text-sm text-gray-500">All finalized versions for this cycle.</p>
                <div class="mt-4 space-y-2">
                    @foreach ($history as $ver)
                        <div class="flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3 text-sm {{ $ver->id === $snapshot->id ? 'ring-2 ring-[#0c6d57]/30' : '' }}">
                            <div>
                                <p class="font-bold text-gray-900">
                                    Version {{ $ver->version_number }}
                                    @if ($ver->is_current)
                                        <span class="ml-2 inline-flex rounded-full bg-green-100 text-green-800 px-2 py-0.5 text-xs font-bold">Current</span>
                                    @endif
                                    @if ($ver->id === $snapshot->id)
                                        <span class="ml-2 inline-flex rounded-full bg-gray-200 text-gray-600 px-2 py-0.5 text-xs font-bold">Viewing</span>
                                    @endif
                                </p>
                                <p class="text-xs text-gray-500">Finalized {{ $ver->finalized_at?->format('M d, Y') }} by {{ $ver->finalizedBy?->name ?? 'N/A' }}</p>
                            </div>
                            @if ($ver->id !== $snapshot->id)
                                <a href="{{ route('profitability.snapshots.show', $ver) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50">View</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>