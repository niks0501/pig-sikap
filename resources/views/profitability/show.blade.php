<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ $cycle->batch_code }} Profitability</h2>
            <p class="text-sm text-gray-500">Cycle financial result computed from recorded sales and expenses.</p>
        </div>
    </x-slot>

    @php
        $money = fn ($value) => '₱'.number_format((float) $value, 2);
        $net = (float) $profitability['net_profit_or_loss'];
        $receivables = (float) ($profitability['receivables'] ?? 0);
        $totalCollected = (float) ($profitability['total_collected'] ?? 0);
        $statusLabels = [
            'profit' => 'Profit',
            'loss' => 'Loss',
            'break_even' => 'Break-even',
            'zero_sales' => 'Zero Sales',
            'insufficient_data' => 'Insufficient Data',
        ];
        $isFinalized = $profitability['is_finalized'] ?? false;
        $statusLabel = $isFinalized ? 'Finalized Official Snapshot' : ($statusLabels[$profitability['status']] ?? 'For Review');
    @endphp

    <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between print:hidden">
            <a href="{{ route('profitability.index') }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Back to Profitability</a>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('cycles.show', $cycle) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Open Cycle Details</a>
                <a href="{{ route('profitability.sharing', $cycle) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">View Profit Sharing</a>
                @if ($isFinalized && $snapshot)
                    <a href="{{ route('profitability.snapshots.show', $snapshot) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">View Snapshot</a>
                    <a href="{{ route('profitability.snapshots.report.preview', $snapshot) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50" target="_blank">Preview Report</a>
                    <a href="{{ route('profitability.snapshots.report.download', $snapshot) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Download Report</a>
                @elseif (! $isFinalized && $profitability['has_sales'])
                    <a href="{{ route('profitability.report.preview', $cycle) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50" target="_blank">Preview Draft Report</a>
                    <a href="{{ route('profitability.report.download', $cycle) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Download Draft Report</a>
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
                    <a href="{{ route('profitability.snapshots.show', $snapshot) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-green-300 bg-white px-4 py-2 text-sm font-semibold text-green-800 transition hover:bg-green-100">View Snapshot History</a>
                </div>
                @if ($dataChanged)
                    <div class="mt-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3">
                        <p class="text-sm font-bold text-amber-900">Data Changed After Finalization</p>
                        <p class="mt-1 text-sm text-amber-800">Expense or sale records have been modified since this snapshot was finalized. Review changes and re-finalize if needed.</p>
                    </div>
                @endif
            </section>
        @endif

        @if (! $isFinalized)
            <section class="rounded-2xl border border-blue-200 bg-blue-50/70 p-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-bold text-blue-900">
                            <span class="inline-flex rounded-full bg-blue-200 text-blue-800 px-2 py-0.5 text-xs font-bold">Live Computation</span>
                        </p>
                        <p class="mt-1 text-sm text-blue-700">Values update in real time as sales and expenses are recorded. Report preview shows draft only.</p>
                    </div>
                </div>
            </section>
        @endif

        <section class="rounded-2xl border {{ $net < 0 ? 'border-rose-200 bg-rose-50' : 'border-[#0c6d57]/20 bg-[#0c6d57]/5' }} p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full {{ $net < 0 ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800' }} px-3 py-1 text-xs font-bold">{{ $statusLabel }}</span>
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

        <section class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Total Collected</p>
                <p class="mt-1 text-lg font-bold text-gray-900">{{ $money($totalCollected) }}</p>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-amber-700">Receivables</p>
                <p class="mt-1 text-lg font-bold text-amber-900">{{ $money($receivables) }}</p>
                @if ($receivables > 0)
                    <p class="mt-1 text-xs text-amber-700">Pending Collection / Receivables</p>
                @endif
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Caretaker</p>
                <p class="mt-1 text-lg font-bold text-gray-900">{{ $cycle->caretaker?->name ?? 'Not assigned' }}</p>
            </div>
        </section>

        @if ($net < 0)
            <section class="rounded-2xl border border-rose-200 bg-rose-50 p-5">
                <p class="text-sm font-bold text-rose-800">Loss / No Distributable Profit</p>
                <p class="mt-1 text-sm text-rose-700">This cycle shows a loss of {{ $money(abs($net)) }}. All stakeholder shares remain ₱0.00. Review expense records and consider documenting this result in a resolution.</p>
            </section>
        @endif

        <section class="grid gap-6 lg:grid-cols-2">
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-bold text-gray-900">Sales Breakdown</h3>
                <p class="mt-1 text-sm text-gray-500">Revenue recorded under this pig cycle.</p>
                <div class="mt-4 space-y-3">
                    @forelse ($profitability['sales_breakdown_rows'] as $row)
                        <div class="flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3 text-sm">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $row['label'] }}</p>
                                @if (($row['pigs_sold'] ?? null) !== null)
                                    <p class="text-xs text-gray-500">{{ number_format((int) $row['pigs_sold']) }} pigs sold</p>
                                @endif
                            </div>
                            <p class="font-bold text-gray-900">{{ $money($row['total']) }}</p>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-amber-300 bg-amber-50 px-4 py-5 text-sm text-amber-800">No sales recorded yet. Profit sharing will remain unavailable until sales are encoded.</div>
                    @endforelse
                </div>
                <div class="mt-4 flex justify-between border-t border-gray-100 pt-4 text-sm font-bold text-gray-900">
                    <span>Gross Revenue</span>
                    <span>{{ $money($profitability['total_sales']) }}</span>
                </div>
            </article>

            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-bold text-gray-900">Expense Breakdown</h3>
                <p class="mt-1 text-sm text-gray-500">Costs deducted before profit sharing.</p>
                <div class="mt-4 space-y-3">
                    @foreach ($profitability['expense_breakdown_rows'] as $row)
                        <div class="flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3 text-sm">
                            <p class="font-semibold text-gray-900">{{ $row['label'] }}</p>
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

        @if (isset($advisory) && $advisory)
            <section class="rounded-2xl border border-blue-200 bg-blue-50/50 p-5 shadow-sm">
                <div class="flex flex-col gap-3">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex rounded-full bg-blue-100 text-blue-700 px-2 py-0.5 text-xs font-bold">Planning Only</span>
                        <h3 class="text-lg font-bold text-gray-900">Break-even Advisory</h3>
                    </div>
                    <p class="text-sm text-gray-500">Advisory estimates for planning decisions. These do not affect official financial records.</p>

                    @if ($advisory['has_live_weight_data'] && $advisory['break_even_price_per_kg'] !== null)
                        <div class="grid gap-3 mt-2 sm:grid-cols-3">
                            <div class="rounded-xl bg-white p-4 border border-gray-200">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Total Live Weight</p>
                                <p class="mt-1 text-lg font-bold text-gray-900">{{ number_format($advisory['total_live_weight_kg'], 2) }} kg</p>
                            </div>
                            <div class="rounded-xl bg-white p-4 border border-gray-200">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Break-even price per kg</p>
                                <p class="mt-1 text-lg font-bold text-gray-900">{{ $money($advisory['break_even_price_per_kg']) }}</p>
                            </div>
                            <div class="rounded-xl bg-white p-4 border border-gray-200">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Avg. actual price per kg</p>
                                <p class="mt-1 text-lg font-bold text-gray-900">{{ $advisory['average_price_per_kg'] !== null ? $money($advisory['average_price_per_kg']) : 'N/A' }}</p>
                            </div>
                        </div>

                        @if (! empty($advisory['projections']))
                            <div class="mt-4 rounded-xl border border-gray-200 bg-white p-4">
                                <p class="text-sm font-bold text-gray-900 mb-3">Price Projections</p>
                                <div class="space-y-2">
                                    @foreach ($advisory['projections'] as $proj)
                                        <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm {{ $proj['is_break_even'] ? 'ring-2 ring-amber-300' : '' }}">
                                            <div>
                                                <p class="font-semibold text-gray-900">At {{ $money($proj['price_per_kg']) }}/kg</p>
                                                <p class="text-xs text-gray-500">{{ $proj['margin_percent'] }}% margin</p>
                                            </div>
                                            <p class="font-bold {{ $proj['projected_profit_or_loss'] < 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ $money($proj['projected_profit_or_loss']) }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="rounded-xl border border-dashed border-gray-300 bg-white px-4 py-4 text-sm text-gray-500">
                            Live weight data is missing. Record live weight in sale records to enable break-even per kg advisory.
                        </div>
                    @endif

                    @if (! empty($advisory['recommendations']))
                        <div class="mt-2 space-y-2">
                            @foreach ($advisory['recommendations'] as $rec)
                                <div class="rounded-xl bg-white border border-gray-200 px-4 py-3 text-sm text-gray-700">{{ $rec }}</div>
                            @endforeach
                        </div>
                    @endif

                    @if (! empty($advisory['warnings']))
                        <div class="mt-2 space-y-2">
                            @foreach ($advisory['warnings'] as $warn)
                                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ $warn }}</div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        @endif

        @if (isset($validation) && ! $isFinalized && isset($isPresident) && $isPresident)
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm print:hidden">
                <h3 class="text-lg font-bold text-gray-900">Finalization Checklist</h3>
                <p class="mt-1 text-sm text-gray-500">All checks must pass before finalizing the official snapshot.</p>
                <div class="mt-4 space-y-2">
                    @foreach ($validation['checklist'] as $item)
                        <div class="flex items-start gap-3 rounded-xl {{ $item['passed'] ? 'bg-green-50 border border-green-200' : 'bg-amber-50 border border-amber-200' }} px-4 py-3">
                            <span class="mt-0.5 shrink-0 text-lg">{{ $item['passed'] ? '✓' : '!' }}</span>
                            <div>
                                <p class="text-sm font-bold {{ $item['passed'] ? 'text-green-800' : 'text-amber-800' }}">{{ $item['label'] }}</p>
                                <p class="text-xs {{ $item['passed'] ? 'text-green-700' : 'text-amber-700' }}">{{ $item['message'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm print:hidden">
            <h3 class="text-lg font-bold text-gray-900">Next Step</h3>
            @if ($net < 0)
                <p class="mt-2 text-sm text-gray-600">This cycle has a loss. Profit shares must remain ₱0.00 and the result should be reviewed for resolution documentation.</p>
            @else
                <p class="mt-2 text-sm text-gray-600">Review the 50/25/25 sharing breakdown before preparing reports or resolution documents.</p>
            @endif
            <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                <a href="{{ route('profitability.sharing', $cycle) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">Review Profit Sharing</a>
                <a href="{{ route('reports.index') }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Open Reports</a>
            </div>
        </section>
    </div>
</x-app-layout>