<x-app-layout>
    @php
        $vueProps = [
            'cycle' => $cycle,
            'pigStatuses' => $pigStatuses,
            'sexOptions' => $sexOptions,
            'routes' => [
                'show' => route('cycles.show', $cycle),
                'store' => route('cycles.profiles.store', $cycle),
                'profilesBase' => route('cycles.profiles.index', $cycle),
                'mortalityCreate' => route('health.mortality.create'),
            ],
            'csrfToken' => csrf_token(),
            'statusMessage' => session('status'),
            'errorMessage' => $errors->first(),
        ];
    @endphp

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div data-vue-component="pig-cycle-pigs" data-props='@json($vueProps)'></div>
    </div>
</x-app-layout>
