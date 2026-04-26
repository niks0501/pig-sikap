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

    <div
        data-vue-component="expense-list"
        data-props="{{ json_encode([
            'expenses' => $expenses->items(),
            'summary' => $summary,
            'filters' => $filters,
            'categories' => array_keys($categoryOptions),
            'cycles' => $cycles->map(function($c) { return ['id' => $c->id, 'batch_code' => $c->batch_code, 'isArchived' => $c->isArchived()]; }),
            'pagination' => [
                'current_page' => $expenses->currentPage(),
                'last_page' => $expenses->lastPage(),
                'per_page' => $expenses->perPage(),
                'total' => $expenses->total(),
            ],
            'routes' => [
                'index' => route('expenses.index'),
                'create' => route('expenses.create'),
                'show' => route('expenses.show', ['expense' => '_ID_']),
                'edit' => route('expenses.edit', ['expense' => '_ID_']),
                'bulkDelete' => route('expenses.bulk-delete'),
            ],
            'csrfToken' => csrf_token(),
            'canBulkDelete' => auth()->user()?->hasRole('president') ?? false,
        ]) }}"
    ></div>
</div>
</x-app-layout>
