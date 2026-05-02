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
    @endphp

    <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between print:hidden">
            <a href="{{ route('profitability.show', $cycle) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Back to Breakdown</a>
            <button type="button" onclick="window.print()" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Print Sharing Report</button>
        </div>

        @if ($snapshot)
            <section class="rounded-2xl border border-gray-300 bg-gray-50 p-5">
                <p class="text-sm font-bold text-gray-900">Finalized Snapshot - read-only</p>
                <p class="mt-1 text-sm text-gray-600">Finalized by {{ $snapshot->finalizedBy?->name ?? 'Unknown user' }} on {{ $snapshot->finalized_at?->format('M d, Y h:i A') }}.</p>
                @if ($snapshot->notes)
                    <p class="mt-2 rounded-xl bg-white px-3 py-2 text-sm text-gray-700">{{ $snapshot->notes }}</p>
                @endif
            </section>
        @endif

        <section class="rounded-2xl border {{ $hasDistribution ? 'border-[#0c6d57]/20 bg-[#0c6d57]/5' : 'border-amber-200 bg-amber-50' }} p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.16em] {{ $hasDistribution ? 'text-[#0c6d57]' : 'text-amber-800' }}">Distributable Profit</p>
            <p class="mt-2 text-4xl font-extrabold {{ $hasDistribution ? 'text-[#0a5a48]' : 'text-amber-900' }}">{{ $money($profitability['distributable_profit']) }}</p>
            <p class="mt-2 text-sm {{ $hasDistribution ? 'text-[#0a5a48]' : 'text-amber-900' }}">
                @if ($net < 0)
                    This cycle has a loss of {{ $money(abs($net)) }}. No profit should be distributed.
                @elseif (! $hasDistribution)
                    This cycle has no profit to distribute. All shares remain ₱0.00.
                @else
                    Distribution is based only on net profit after all recorded expenses are deducted.
                @endif
            </p>
        </section>

        <section class="grid gap-4 md:grid-cols-3">
            <article class="rounded-2xl border border-[#0c6d57]/20 bg-white p-5 text-center shadow-sm">
                <p class="text-sm font-bold text-[#0c6d57]">Caretaker / Nag-alaga</p>
                <p class="mt-1 text-xs text-gray-500">50% share</p>
                <p class="mt-4 border-t border-gray-100 pt-4 text-3xl font-extrabold text-gray-900">{{ $money($profitability['caretaker_share']) }}</p>
            </article>
            <article class="rounded-2xl border border-[#0c6d57]/20 bg-white p-5 text-center shadow-sm">
                <p class="text-sm font-bold text-[#0c6d57]">Association Members</p>
                <p class="mt-1 text-xs text-gray-500">25% share</p>
                <p class="mt-4 border-t border-gray-100 pt-4 text-3xl font-extrabold text-gray-900">{{ $money($profitability['member_share']) }}</p>
            </article>
            <article class="rounded-2xl border border-[#0c6d57]/20 bg-white p-5 text-center shadow-sm">
                <p class="text-sm font-bold text-[#0c6d57]">Association Fund / Samahan</p>
                <p class="mt-1 text-xs text-gray-500">25% share</p>
                <p class="mt-4 border-t border-gray-100 pt-4 text-3xl font-extrabold text-gray-900">{{ $money($profitability['association_share']) }}</p>
            </article>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900">Distribution Check</h3>
            <p class="mt-2 text-sm text-gray-600">
                {{ $money($profitability['caretaker_share']) }} + {{ $money($profitability['member_share']) }} + {{ $money($profitability['association_share']) }} = {{ $money($profitability['distributable_profit']) }}
            </p>
        </section>

        @if ($canFinalize)
            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm print:hidden">
                <h3 class="text-lg font-bold text-amber-900">Finalize for Reports and Resolutions</h3>
                <p class="mt-2 text-sm text-amber-900">After finalizing, this snapshot is locked as the official profitability basis for reports and approval documents.</p>
                <form method="POST" action="{{ route('profitability.finalize', $cycle) }}" class="mt-4 space-y-3">
                    @csrf
                    <label class="block">
                        <span class="mb-1 block text-sm font-bold text-gray-700">Finalization Notes (optional)</span>
                        <textarea name="notes" rows="3" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-[#0c6d57] focus:outline-none focus:ring-2 focus:ring-[#0c6d57]/20" placeholder="Example: Approved after reviewing expense and sales records.">{{ old('notes') }}</textarea>
                        @error('notes')
                            <span class="mt-1 block text-sm font-semibold text-rose-700">{{ $message }}</span>
                        @enderror
                    </label>
                    @error('cycle')
                        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">{{ $message }}</div>
                    @enderror
                    <button type="submit" class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl bg-[#0c6d57] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0a5a48] sm:w-auto">Finalize Snapshot</button>
                </form>
            </section>
        @elseif (! $snapshot)
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm print:hidden">
                <h3 class="text-lg font-bold text-gray-900">Snapshot Not Finalized</h3>
                <p class="mt-2 text-sm text-gray-600">Only the President can finalize profitability after the cycle is completed, sold, or closed.</p>
            </section>
        @endif
    </div>
</x-app-layout>
