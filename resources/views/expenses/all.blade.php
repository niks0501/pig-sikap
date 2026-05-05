<x-app-layout>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">All Expenses</h1>
            <p class="text-sm text-gray-500 mt-1">Combined view of cycle and association expenses.</p>
        </div>
    </div>

    @if (session('status'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        {{ session('status') }}
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
        data-vue-component="all-expenses-list"
        data-props="{{ json_encode([
            'expenses' => $expenses->items(),
            'filters' => $filters,
            'categories' => array_keys($categoryOptions),
            'pagination' => [
                'current_page' => $expenses->currentPage(),
                'last_page' => $expenses->lastPage(),
                'per_page' => $expenses->perPage(),
                'total' => $expenses->total(),
            ],
            'routes' => [
                'index' => route('expenses.all'),
            ],
            'csrfToken' => csrf_token(),
        ]) }}"
    ></div>
</div>
</x-app-layout>
