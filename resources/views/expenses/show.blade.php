<x-app-layout>
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
<div class="flex items-center gap-4 mb-6">
<a href="{{ route('expenses.index') }}" class="p-2 -ml-2 rounded-xl text-gray-500 hover:bg-white hover:text-gray-900 transition">
<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
</a>
<div>
<h1 class="text-2xl font-bold text-gray-900">Expense Details</h1>
<p class="text-sm text-gray-500 mt-1">View the details of this recorded expense entry.</p>
</div>
</div>

<div
data-vue-component="expense-detail"
        data-props="{{ json_encode([
            'expense' => [
                'id' => $expense->id,
                'batch_id' => $expense->batch_id,
                'category' => $expense->category,
                'quantity' => $expense->quantity !== null ? (float) $expense->quantity : null,
                'unit' => $expense->unit,
                'unit_cost' => $expense->unit_cost !== null ? (float) $expense->unit_cost : null,
                'amount' => (float) $expense->amount,
                'expense_date' => $expense->expense_date?->toDateString(),
                'notes' => $expense->notes,
                'receipt_url' => $expense->receiptUrl(),
                'created_at' => $expense->created_at?->toISOString(),
                'updated_at' => $expense->updated_at?->toISOString(),
                'created_by_name' => $expense->createdBy?->name,
                'updated_by_name' => $expense->updatedBy?->name,
                'cycle' => [
                    'batch_code' => $expense->cycle?->batch_code,
                    'isArchived' => $expense->cycle?->isArchived() ?? false,
                    'status' => $expense->cycle?->status,
                ],
            ],
            'routes' => [
                'index' => route('expenses.index'),
                'edit' => route('expenses.edit', ['expense' => '_ID_']),
                'duplicate' => route('expenses.duplicate', ['expense' => '_ID_']),
                'destroy' => route('expenses.destroy', ['expense' => '_ID_']),
            ],
            'csrfToken' => csrf_token(),
        ]) }}"
    ></div>
</div>
</x-app-layout>
