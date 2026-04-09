<x-app-layout>
    @php
        $vueProps = [
            'batch' => $batch,
            'stages' => $stages,
            'statuses' => $statuses,
            'breeders' => $breeders,
            'caretakers' => $caretakers,
            'routes' => [
                'show' => route('batches.show', $batch),
                'update' => route('batches.update', $batch),
            ],
            'csrfToken' => csrf_token(),
            'oldInput' => old(),
            'errors' => $errors->toArray(),
            'statusMessage' => session('status'),
            'errorMessage' => $errors->first(),
        ];
    @endphp

    <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
        <div data-vue-component="pig-batch-edit" data-props='@json($vueProps)'></div>
    </div>
</x-app-layout>
