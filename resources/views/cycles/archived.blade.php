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
            'search' => $search,
            'routes' => [
                'index' => route('cycles.index'),
                'archived' => route('cycles.archived'),
                'showBase' => url('/cycles'),
                'destroyBase' => url('/cycles'),
            ],
            'csrfToken' => csrf_token(),
            'statusMessage' => session('status'),
            'errorMessage' => $errors->first(),
        ];
    @endphp

    <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div data-vue-component="pig-cycle-archived" data-props='@json($vueProps)'></div>
    </div>
</x-app-layout>
