<x-app-layout>
    @php
        $vueProps = [
            'batchCode' => $batchCode,
            'stages' => $stages,
            'statuses' => $statuses,
            'breeders' => $breeders,
            'caretakers' => $caretakers,
            'routes' => [
                'index' => route('batches.index'),
                'store' => route('batches.store'),
            ],
            'csrfToken' => csrf_token(),
            'oldInput' => old(),
            'errors' => $errors->toArray(),
            'statusMessage' => session('status'),
            'errorMessage' => $errors->first(),
        ];
    @endphp

    <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
        <div data-vue-component="pig-batch-create" data-props='@json($vueProps)'></div>
    </div>
</x-app-layout>
