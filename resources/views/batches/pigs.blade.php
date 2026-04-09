<x-app-layout>
    @php
        $vueProps = [
            'batch' => $batch,
            'pigStatuses' => $pigStatuses,
            'sexOptions' => $sexOptions,
            'routes' => [
                'show' => route('batches.show', $batch),
                'store' => route('batches.pigs.store', $batch),
                'pigsBase' => route('batches.pigs.index', $batch),
            ],
            'csrfToken' => csrf_token(),
            'statusMessage' => session('status'),
            'errorMessage' => $errors->first(),
        ];
    @endphp

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div data-vue-component="pig-batch-pigs" data-props='@json($vueProps)'></div>
    </div>
</x-app-layout>
