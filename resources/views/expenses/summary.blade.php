<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div class="flex items-center gap-3">
                <a href="{{ route('expenses.index') }}" class="p-2 -ml-2 rounded-xl text-gray-500 hover:bg-white hover:text-gray-900 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Expense Summary</h1>
                    <p class="text-sm text-gray-500 mt-1">Review totals by cycle and category.</p>
                </div>
            </div>
            <a href="{{ route('expenses.create', ['cycle_id' => $cycleId]) }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-[#0c6d57] text-white font-semibold hover:bg-[#0a5a48] transition-colors">
                Add Expense
            </a>
        </div>

        <form method="GET" action="{{ route('expenses.summary') }}" class="bg-white rounded-xl border border-gray-100 p-4 mb-6 grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label for="timeframe" class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Timeframe</label>
                <select id="timeframe" name="timeframe" class="w-full rounded-lg border-gray-200 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]">
                    <option value="this_month" @selected($timeframe === 'this_month')>This Month</option>
                    <option value="last_month" @selected($timeframe === 'last_month')>Last Month</option>
                    <option value="all_time" @selected($timeframe === 'all_time')>All Time</option>
                </select>
            </div>
            <div>
                <label for="cycle_id" class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Cycle</label>
                <select id="cycle_id" name="cycle_id" class="w-full rounded-lg border-gray-200 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]">
                    <option value="">All cycles</option>
                    @foreach ($cycles as $cycle)
                        <option value="{{ $cycle->id }}" @selected((string) $cycleId === (string) $cycle->id)>
                            {{ $cycle->batch_code }}{{ $cycle->isArchived() ? ' (Archived)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="inline-flex justify-center items-center px-4 py-2.5 rounded-lg bg-[#0c6d57] text-white font-semibold hover:bg-[#0a5a48] transition-colors">
                    Update
                </button>
                <a href="{{ route('expenses.summary') }}" class="inline-flex justify-center items-center px-4 py-2.5 rounded-lg border border-gray-200 text-gray-700 font-semibold hover:bg-gray-50 transition-colors">
                    Reset
                </a>
            </div>
        </form>

        <div class="bg-[#0c6d57] rounded-2xl px-6 py-8 sm:p-10 text-white mb-6">
            <p class="text-sm font-semibold uppercase tracking-wider text-white/80">Total Expenses</p>
            <p class="mt-2 text-4xl sm:text-5xl font-black">Php {{ number_format((float) $summary['total_amount'], 2) }}</p>
            <p class="mt-2 text-sm text-white/80">{{ number_format((int) $summary['entry_count']) }} recorded entries | Feed share {{ number_format((float) $summary['feed_share_percent'], 2) }}%</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            @foreach ($summary['by_category'] as $category => $amount)
                <div class="bg-white rounded-xl border border-gray-100 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $categoryOptions[$category] ?? ucfirst($category) }}</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">Php {{ number_format((float) $amount, 2) }}</p>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">Recent Entries in Selected Scope</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Category</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Notes</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($recentExpenses as $expense)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $expense->expense_date?->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $categoryOptions[$expense->category] ?? ucfirst($expense->category) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $expense->notes }}</td>
                                <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">Php {{ number_format((float) $expense->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">No expenses found for the selected scope.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
