<x-app-layout>
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('expenses.association.index') }}" class="p-2 -ml-2 rounded-xl text-gray-500 hover:bg-white hover:text-gray-900 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Record Association Expense</h1>
            <p class="text-sm text-gray-500 mt-1">Add an expense not tied to a pig cycle.</p>
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
            'formMode' => 'create',
            'suppliers' => $suppliers->toArray(),
            'canvasses' => $canvasses->toArray(),
            'resolutions' => $resolutions->toArray(),
            'routes' => [
                'store' => route('expenses.association.store'),
                'index' => route('expenses.association.index'),
            ],
            'csrfToken' => csrf_token(),
            'oldInput' => old(),
            'errors' => $errors->toArray(),
            'flashStatus' => session('status'),
        ]) }}"
    ></div>
</div>
</x-app-layout>
