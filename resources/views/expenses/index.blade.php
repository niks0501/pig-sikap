<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Expense Records</h1>
                <p class="text-sm text-gray-500 mt-1">Digital expense logbook for each pig cycle.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                <a href="{{ route('expenses.summary', request()->query()) }}" class="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 px-4 py-2.5 bg-white border border-[#0c6d57] text-[#0c6d57] font-semibold rounded-xl hover:bg-[#0c6d57]/5 transition-colors">
                    View Summary
                </a>
                <a href="{{ route('expenses.create', ['cycle_id' => $filters['cycle_id']]) }}" class="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 px-4 py-2.5 bg-[#0c6d57] text-white font-semibold rounded-xl hover:bg-[#0a5a48] transition-colors">
                    Add Expense
                </a>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-gray-100 p-4">
                <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Filtered Total</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">Php {{ number_format((float) $summary['total_amount'], 2) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4">
                <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Entries</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format((int) $summary['entry_count']) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4">
                <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Feed Share</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format((float) $summary['feed_share_percent'], 2) }}%</p>
            </div>
        </div>

        <form method="GET" action="{{ route('expenses.index') }}" class="bg-white rounded-xl border border-gray-100 p-4 mb-6 grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label for="search" class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Search</label>
                <input id="search" name="search" value="{{ $filters['search'] }}" type="text" placeholder="Description or cycle" class="w-full rounded-lg border-gray-200 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]" />
            </div>

            <div>
                <label for="category" class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Category</label>
                <select id="category" name="category" class="w-full rounded-lg border-gray-200 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]">
                    <option value="">All categories</option>
                    @foreach ($categoryOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['category'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="cycle_id" class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Cycle</label>
                <select id="cycle_id" name="cycle_id" class="w-full rounded-lg border-gray-200 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]">
                    <option value="">All cycles</option>
                    @foreach ($cycles as $cycle)
                        <option value="{{ $cycle->id }}" @selected($filters['cycle_id'] === (string) $cycle->id)>
                            {{ $cycle->batch_code }}{{ $cycle->isArchived() ? ' (Archived)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="month" class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Month</label>
                <input id="month" name="month" type="month" value="{{ $filters['month'] }}" class="w-full rounded-lg border-gray-200 text-sm focus:border-[#0c6d57] focus:ring-[#0c6d57]" />
            </div>

            <div class="md:col-span-4 flex flex-col sm:flex-row gap-2">
                <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2.5 rounded-lg bg-[#0c6d57] text-white font-semibold hover:bg-[#0a5a48] transition-colors">
                    Apply Filters
                </button>
                <a href="{{ route('expenses.index') }}" class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2.5 rounded-lg border border-gray-200 text-gray-700 font-semibold hover:bg-gray-50 transition-colors">
                    Clear
                </a>
            </div>
        </form>

        @if ($expenses->isEmpty())
            <div class="bg-white rounded-xl border border-gray-100 p-8 text-center">
                <h2 class="text-lg font-semibold text-gray-900">No expense records found</h2>
                <p class="text-sm text-gray-500 mt-1">Try changing filters or add a new expense entry.</p>
                <a href="{{ route('expenses.create') }}" class="mt-4 inline-flex items-center justify-center px-4 py-2 rounded-lg bg-[#0c6d57] text-white font-semibold hover:bg-[#0a5a48] transition-colors">
                    Add First Expense
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 gap-3 sm:hidden mb-4">
                @foreach ($expenses as $expense)
                    <a href="{{ route('expenses.show', $expense) }}" class="bg-white rounded-xl border border-gray-100 p-4 block hover:border-[#0c6d57]/40 transition-colors">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $expense->categoryLabel() }}</p>
                                <p class="text-sm font-semibold text-gray-900 mt-1">{{ $expense->notes }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ $expense->cycle?->batch_code ?? 'Unknown cycle' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-base font-bold text-gray-900">Php {{ number_format((float) $expense->amount, 2) }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ $expense->expense_date?->format('M d, Y') }}</p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="hidden sm:block bg-white rounded-xl border border-gray-100 overflow-hidden mb-4">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Category</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Notes</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Cycle</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Recorded By</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Amount</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($expenses as $expense)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $expense->expense_date?->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $expense->categoryLabel() }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $expense->notes }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $expense->cycle?->batch_code ?? 'Unknown cycle' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $expense->createdBy?->name ?? 'System' }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-gray-900 text-right">Php {{ number_format((float) $expense->amount, 2) }}</td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <a href="{{ route('expenses.show', $expense) }}" class="text-[#0c6d57] font-semibold hover:text-[#0a5a48]">Details</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $expenses->links() }}
        @endif
    </div>
</x-app-layout>
