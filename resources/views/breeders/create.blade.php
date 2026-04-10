<x-app-layout>
    @php
        $initialData = [
            'data' => $breeders->items(),
            'meta' => [
                'current_page' => $breeders->currentPage(),
                'last_page' => $breeders->lastPage(),
                'per_page' => $breeders->perPage(),
                'total' => $breeders->total(),
            ],
        ];

        $vueProps = [
            'initialData' => $initialData,
            'search' => $search,
            'reproductiveStatuses' => $reproductiveStatuses,
            'routes' => [
                'index' => route('breeders.create'),
                'store' => route('breeders.store'),
                'batches' => route('cycles.index'),
            ],
            'csrfToken' => csrf_token(),
            'oldInput' => old(),
            'errors' => $errors->toArray(),
            'statusMessage' => session('status'),
            'errorMessage' => $errors->first(),
        ];
    @endphp

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div data-vue-component="pig-breeder-registry" data-props='@json($vueProps)'></div>
    </div>
</x-app-layout>
