<x-app-layout>
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('expenses.show', $expense) }}" class="p-2 -ml-2 rounded-xl text-gray-500 hover:bg-white hover:text-gray-900 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Expense</h1>
            <p class="text-sm text-gray-500 mt-1">Update a recorded expense entry.</p>
        </div>
    </div>

    <div
        data-vue-component="expense-form"
        data-props="{{ json_encode([
            'cycles' => $cycles->map(function($c) { return ['id' => $c->id, 'batch_code' => $c->batch_code, 'isArchived' => $c->isArchived()]; }),
            'categories' => array_keys($categoryOptions),
            'formMode' => 'edit',
            'expense' => [
                'id' => $expense->id,
                'batch_id' => $expense->batch_id,
                'category' => $expense->category,
                'amount' => (float) $expense->amount,
                'expense_date' => $expense->expense_date?->toDateString(),
                'notes' => $expense->notes,
                'receipt_url' => $expense->receiptUrl(),
            ],
            'routes' => [
                'store' => route('expenses.store'),
                'update' => route('expenses.update', ['expense' => '_ID_']),
                'index' => route('expenses.index'),
                'show' => route('expenses.show', ['expense' => '_ID_']),
                'preferences' => route('expenses.preferences'),
                'preferencesUpdate' => route('expenses.preferences.update'),
                'recentTemplates' => route('expenses.recent-templates'),
            ],
            'csrfToken' => csrf_token(),
            'oldInput' => old(),
            'errors' => $errors->toArray(),
            'flashStatus' => session('status'),
        ]) }}"
    ></div>
</div>
</x-app-layout>
