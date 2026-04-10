<x-app-layout>
    @php
        $initialData = [
            'data' => $cycles->items(),
            'meta' => [
                'current_page' => $cycles->currentPage(),
                'last_page' => $cycles->lastPage(),
                'per_page' => $cycles->perPage(),
                'total' => $cycles->total(),
            ],
        ];

        $vueProps = [
            'initialData' => $initialData,
            'initialFilters' => $filters,
            'summary' => $summary,
            'recentUpdates' => $recentUpdates,
            'stages' => $stages,
            'statuses' => $statuses,
            'caretakers' => $caretakers,
            'routes' => [
                'index' => route('cycles.index'),
                'create' => route('cycles.create'),
                'archived' => route('cycles.archived'),
                'breeders' => route('breeders.create'),
                'showBase' => url('/cycles'),
            ],
            'statusMessage' => session('status'),
            'errorMessage' => $errors->first(),
        ];
    @endphp

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div data-vue-component="pig-registry-index" data-props='@json($vueProps)'></div>
    </div>
</x-app-layout>
