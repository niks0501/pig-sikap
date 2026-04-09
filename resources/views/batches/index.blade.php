<x-app-layout>
    @php
        $initialData = [
            'data' => $batches->items(),
            'meta' => [
                'current_page' => $batches->currentPage(),
                'last_page' => $batches->lastPage(),
                'per_page' => $batches->perPage(),
                'total' => $batches->total(),
            ],
        ];

        $vueProps = [
            'initialData' => $initialData,
            'initialFilters' => $filters,
            'summary' => $summary,
            'recentUpdates' => $recentUpdates,
            'stages' => $stages,
            'statuses' => $statuses,
            'breeders' => $breeders,
            'caretakers' => $caretakers,
            'routes' => [
                'index' => route('batches.index'),
                'create' => route('batches.create'),
                'archived' => route('batches.archived'),
                'breeders' => route('breeders.create'),
                'showBase' => url('/batches'),
            ],
            'statusMessage' => session('status'),
            'errorMessage' => $errors->first(),
        ];
    @endphp

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div data-vue-component="pig-registry-index" data-props='@json($vueProps)'></div>
    </div>
</x-app-layout>
