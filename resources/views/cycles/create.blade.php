<x-app-layout>
    @php
        $vueProps = [
            'cycleCode' => $cycleCode,
            'stages' => $stages,
            'statuses' => $statuses,
            'caretakers' => $caretakers,
            'routes' => [
                'index' => route('cycles.index'),
                'store' => route('cycles.store'),
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
