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
            'search' => $search,
            'routes' => [
                'index' => route('batches.index'),
                'archived' => route('batches.archived'),
                'showBase' => url('/batches'),
                'destroyBase' => url('/batches'),
            ],
            'csrfToken' => csrf_token(),
            'statusMessage' => session('status'),
            'errorMessage' => $errors->first(),
        ];
    @endphp

    <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div data-vue-component="pig-batch-archived" data-props='@json($vueProps)'></div>
    </div>
</x-app-layout>
