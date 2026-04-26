<x-app-layout>
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('expenses.index') }}" class="p-2 -ml-2 rounded-xl text-gray-500 hover:bg-white hover:text-gray-900 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Record Expense</h1>
            <p class="text-sm text-gray-500 mt-1">Add one expense entry to a selected cycle.</p>
        </div>
    </div>

    @if ($errors->any())
    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        Please review the form and correct the highlighted fields.
    </div>
    @endif

    <div
        data-vue-component="expense-form"
        data-props="{{ json_encode([
            'cycles' => $cycles->map(function($c) { return ['id' => $c->id, 'batch_code' => $c->batch_code, 'isArchived' => $c->isArchived()]; }),
            'categories' => array_keys($categoryOptions),
            'selectedCycleId' => (int) $selectedCycleId,
            'formMode' => 'create',
            'preferences' => $preferences,
            'recentTemplates' => $recentTemplates,
            'routes' => [
                'store' => route('expenses.store'),
                'index' => route('expenses.index'),
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
