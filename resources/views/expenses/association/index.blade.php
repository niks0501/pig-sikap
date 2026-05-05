<x-app-layout>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Association Expenses</h1>
            <p class="text-sm text-gray-500 mt-1">Expenses not tied to a pig cycle: meeting costs, supplies, bank fees, emergencies.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <a href="{{ route('expenses.association.create') }}" class="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 px-4 py-2.5 bg-[#0c6d57] text-white font-semibold rounded-xl hover:bg-[#0a5a48] transition-colors">
                Add Association Expense
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

    {{-- Tab bar --}}
    <div class="mb-4 flex overflow-x-auto rounded-xl bg-white border border-gray-100 p-1 shadow-sm">
        @php
            $currentTab = request()->route()->getName();
            $tabs = [
                'expenses.index' => ['label' => 'Cycle Expenses', 'url' => route('expenses.index')],
                'expenses.association.index' => ['label' => 'Association Expenses', 'url' => route('expenses.association.index')],
                'expenses.all' => ['label' => 'All Expenses', 'url' => route('expenses.all')],
            ];
        @endphp
        @foreach ($tabs as $routeName => $tab)
            <a
                href="{{ $tab['url'] }}"
                class="flex-1 inline-flex justify-center items-center px-4 py-2.5 text-sm font-semibold rounded-lg transition-colors {{ $currentTab === $routeName ? 'bg-[#0c6d57] text-white' : 'text-gray-600 hover:bg-gray-100' }}"
            >
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>

    <div
        data-vue-component="association-expense-list"
        data-props="{{ json_encode([
            'expenses' => collect($expenses->items())->map(function($expense) {
                return [
                    'id' => $expense->id,
                    'item_name' => $expense->item_name,
                    'category' => $expense->category,
                    'feed_subcategory' => $expense->feed_subcategory,
                    'quantity' => $expense->quantity !== null ? (float) $expense->quantity : null,
                    'unit' => $expense->unit,
                    'unit_cost' => $expense->unit_cost !== null ? (float) $expense->unit_cost : null,
                    'amount' => (float) $expense->amount,
                    'expense_date' => $expense->expense_date?->toDateString(),
                    'receipt_reference' => $expense->receipt_reference,
                    'receipt_url' => $expense->receiptUrl(),
                    'fund_source' => $expense->fund_source,
                    'notes' => $expense->notes,
                    'supplier' => $expense->supplier ? ['id' => $expense->supplier->id, 'name' => $expense->supplier->name] : null,
                    'approved_resolution' => $expense->approvedResolution ? [
                        'id' => $expense->approvedResolution->id,
                        'title' => $expense->approvedResolution->title,
                        'resolution_number' => $expense->approvedResolution->resolution_number,
                    ] : null,
                    'withdrawal' => $expense->withdrawal ? [
                        'id' => $expense->withdrawal->id,
                        'amount' => (float) $expense->withdrawal->amount,
                        'status' => $expense->withdrawal->status,
                    ] : null,
                    'created_by_name' => $expense->createdBy?->name,
                ];
            })->values(),
            'summary' => $summary,
            'filters' => $filters,
            'categories' => array_keys($categoryOptions),
            'feedSubcategories' => array_keys($feedSubcategoryOptions),
            'fundSources' => array_keys($fundSourceOptions),
            'suppliers' => $suppliers->toArray(),
            'resolutions' => $resolutions->toArray(),
            'pagination' => [
                'current_page' => $expenses->currentPage(),
                'last_page' => $expenses->lastPage(),
                'per_page' => $expenses->perPage(),
                'total' => $expenses->total(),
            ],
            'routes' => [
                'index' => route('expenses.association.index'),
                'create' => route('expenses.association.create'),
                'store' => route('expenses.association.store'),
                'show' => route('expenses.association.show', ['expense' => '_ID_']),
                'edit' => route('expenses.association.edit', ['expense' => '_ID_']),
                'update' => route('expenses.association.update', ['expense' => '_ID_']),
                'destroy' => route('expenses.association.destroy', ['expense' => '_ID_']),
            ],
            'csrfToken' => csrf_token(),
        ]) }}"
    ></div>
</div>
</x-app-layout>
