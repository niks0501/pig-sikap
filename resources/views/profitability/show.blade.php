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
        $statusLabels = [
            'profit' => 'Profit',
            'loss' => 'Loss',
            'break_even' => 'Break-even',
            'zero_sales' => 'Zero Sales',
            'insufficient_data' => 'Insufficient Data',
        ];
        $statusLabel = $profitability['is_finalized'] ? 'Finalized Snapshot' : ($statusLabels[$profitability['status']] ?? 'For Review');
    @endphp

    <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between print:hidden">
            <a href="{{ route('profitability.index') }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Back to Profitability</a>
            <div class="flex flex-col gap-2 sm:flex-row">
                <a href="{{ route('cycles.show', $cycle) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Open Cycle Details</a>
                <a href="{{ route('profitability.sharing', $cycle) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">View Profit Sharing</a>
            </div>
        </div>

        @if ($snapshot)
            <section class="rounded-2xl border border-gray-300 bg-gray-50 p-5">
                <p class="text-sm font-bold text-gray-900">Finalized Snapshot - read-only</p>
                <p class="mt-1 text-sm text-gray-600">Finalized by {{ $snapshot->finalizedBy?->name ?? 'Unknown user' }} on {{ $snapshot->finalized_at?->format('M d, Y h:i A') }}. These values are locked for reports and resolutions.</p>
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

        <section class="grid gap-6 lg:grid-cols-2">
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-bold text-gray-900">Sales Breakdown</h3>
                <p class="mt-1 text-sm text-gray-500">Revenue recorded under this pig cycle.</p>
                <div class="mt-4 space-y-3">
                    @forelse ($profitability['sales_breakdown_rows'] as $row)
                        <div class="flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3 text-sm">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $row['label'] }}</p>
                                @if ($row['pigs_sold'] !== null)
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

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900">Next Step</h3>
            @if ($net < 0)
                <p class="mt-2 text-sm text-gray-600">This cycle has a loss. Profit shares must remain ₱0.00 and the result should be reviewed for resolution documentation.</p>
            @else
                <p class="mt-2 text-sm text-gray-600">Review the 50/25/25 sharing breakdown before preparing reports or resolution documents.</p>
            @endif
            <div class="mt-4 flex flex-col gap-2 sm:flex-row print:hidden">
                <a href="{{ route('profitability.sharing', $cycle) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">Review Profit Sharing</a>
                <a href="{{ route('reports.index') }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Open Reports</a>
            </div>
        </section>
    </div>
</x-app-layout>
