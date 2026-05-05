<x-app-layout>
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('expenses.association.index') }}" class="p-2 -ml-2 rounded-xl text-gray-500 hover:bg-white hover:text-gray-900 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Association Expense</h1>
            <p class="text-sm text-gray-500 mt-1">Update the association expense details.</p>
        </div>
    </div>

    @if ($errors->any())
    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        Please review the form and correct the highlighted fields.
    </div>
    @endif

    <div
        data-vue-component="association-expense-form"
        data-props="{{ json_encode([
            'categories' => array_keys($categoryOptions),
            'feedSubcategories' => array_keys($feedSubcategoryOptions),
            'fundSources' => array_keys($fundSourceOptions),
            'formMode' => 'edit',
            'expense' => [
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
                'supplier_id' => $expense->supplier_id,
                'canvass_id' => $expense->canvass_id,
                'fund_source' => $expense->fund_source,
                'approved_resolution_id' => $expense->approved_resolution_id,
                'withdrawal_id' => $expense->withdrawal_id,
                'notes' => $expense->notes,
            ],
            'suppliers' => $suppliers->toArray(),
            'canvasses' => $canvasses->toArray(),
            'resolutions' => $resolutions->toArray(),
            'routes' => [
                'store' => route('expenses.association.store'),
                'update' => route('expenses.association.update', ['expense' => $expense->id]),
                'index' => route('expenses.association.index'),
                'show' => route('expenses.association.show', ['expense' => $expense->id]),
            ],
            'csrfToken' => csrf_token(),
            'oldInput' => old(),
            'errors' => $errors->toArray(),
            'flashStatus' => session('status'),
        ]) }}"
    ></div>
</div>
</x-app-layout>
