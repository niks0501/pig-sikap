<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Profitability Overview</h2>
            <p class="text-sm text-gray-500">Review sales, expenses, net results, and finalized sharing records per cycle.</p>
        </div>
    </x-slot>

    @php
        $money = fn ($value) => '₱'.number_format((float) $value, 2);
        $statusLabels = [
            'profit' => 'Profit',
            'loss' => 'Loss',
            'break_even' => 'Break-even',
            'zero_sales' => 'Zero Sales',
            'insufficient_data' => 'Insufficient Data',
        ];
        $statusClasses = [
            'profit' => 'bg-emerald-100 text-emerald-800',
            'loss' => 'bg-rose-100 text-rose-800',
            'break_even' => 'bg-blue-100 text-blue-800',
            'zero_sales' => 'bg-amber-100 text-amber-800',
            'insufficient_data' => 'bg-gray-100 text-gray-700',
        ];
    @endphp

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">Total Sales</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ $money($summary['total_sales']) }}</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">Total Expenses</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ $money($summary['total_expenses']) }}</p>
            </article>
            <article class="rounded-2xl border {{ $summary['net_profit_or_loss'] < 0 ? 'border-rose-200 bg-rose-50' : 'border-[#0c6d57]/20 bg-[#0c6d57]/5' }} p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] {{ $summary['net_profit_or_loss'] < 0 ? 'text-rose-700' : 'text-[#0c6d57]' }}">Net Result</p>
                <p class="mt-2 text-2xl font-bold {{ $summary['net_profit_or_loss'] < 0 ? 'text-rose-800' : 'text-[#0a5a48]' }}">{{ $money($summary['net_profit_or_loss']) }}</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">Finalized Snapshots</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ $summary['finalized_count'] }} / {{ $summary['cycles_count'] }}</p>
            </article>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 p-5">
                <h3 class="text-lg font-bold text-gray-900">Cycle Profitability Records</h3>
                <p class="mt-1 text-sm text-gray-500">Values are computed from encoded expense and sale logs. Finalized rows use locked snapshot values.</p>
            </div>

            <div class="divide-y divide-gray-100">
                @forelse ($cycles as $cycle)
                    @php
                        $profitability = $cycleSummaries[$cycle->id];
                        $status = $profitability['is_finalized'] ? 'finalized' : $profitability['status'];
                        $statusLabel = $profitability['is_finalized'] ? 'Finalized' : ($statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status)));
                        $statusClass = $profitability['is_finalized'] ? 'bg-gray-900 text-white' : ($statusClasses[$status] ?? 'bg-gray-100 text-gray-700');
                    @endphp

                    <article class="p-5 transition hover:bg-gray-50">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="text-base font-bold text-gray-900">{{ $cycle->batch_code }}</h4>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                                    @if ($cycle->isArchived())
                                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">Archived</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm text-gray-500">
                                    Caretaker: {{ $cycle->caretaker?->name ?? 'Unassigned' }} · {{ $cycle->stage }} / {{ $cycle->status }}
                                </p>
                                <div class="mt-3 grid gap-2 text-sm sm:grid-cols-3">
                                    <div class="rounded-xl bg-gray-50 px-3 py-2"><span class="text-gray-500">Sales</span><br><strong>{{ $money($profitability['total_sales']) }}</strong></div>
                                    <div class="rounded-xl bg-gray-50 px-3 py-2"><span class="text-gray-500">Expenses</span><br><strong>{{ $money($profitability['total_expenses']) }}</strong></div>
                                    <div class="rounded-xl bg-gray-50 px-3 py-2"><span class="text-gray-500">Net</span><br><strong class="{{ $profitability['net_profit_or_loss'] < 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ $money($profitability['net_profit_or_loss']) }}</strong></div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-2 sm:flex-row lg:flex-col xl:flex-row">
                                <a href="{{ route('profitability.show', $cycle) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-[#0c6d57]/30 bg-white px-4 py-2 text-sm font-semibold text-[#0c6d57] transition hover:bg-[#0c6d57]/5">
                                    View Breakdown
                                </a>
                                <a href="{{ route('profitability.sharing', $cycle) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">
                                    Profit Sharing
                                </a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="p-8 text-center">
                        <h3 class="text-base font-bold text-gray-900">No pig cycles yet</h3>
                        <p class="mt-2 text-sm text-gray-500">Create a cycle, then record expenses and sales to see profitability.</p>
                        <a href="{{ route('cycles.create') }}" class="mt-4 inline-flex min-h-[44px] items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0a5a48]">Create Cycle</a>
                    </div>
                @endforelse
            </div>
        </section>

        {{ $cycles->links() }}
    </div>
</x-app-layout>
