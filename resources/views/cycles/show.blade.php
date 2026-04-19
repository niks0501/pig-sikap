<x-app-layout>
    @php
        $vueProps = [
            'cycle' => $cycle,
            'adjustmentTypes' => $adjustmentTypes,
            'adjustmentReasons' => $adjustmentReasons,
            'stages' => $stages,
            'statuses' => $statuses,
            'pigStatuses' => $pigStatuses,
            'sexOptions' => $sexOptions,
            'automation' => $automation,
            'routes' => [
                'index' => route('cycles.index'),
                'edit' => route('cycles.edit', $cycle),
                'archive' => route('cycles.archive', $cycle),
                'profilesIndex' => route('cycles.profiles.index', $cycle),
                'adjustmentsStore' => route('cycles.adjustments.store', $cycle),
                'statusStore' => route('cycles.status.store', $cycle),
                'healthIndex' => route('health.index'),
                'healthCycleTimeline' => route('health.cycles.show', $cycle),
            ],
            'csrfToken' => csrf_token(),
            'statusMessage' => session('status'),
            'errorMessage' => $errors->first(),
        ];
    @endphp

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div data-vue-component="pig-cycle-show" data-props='@json($vueProps)'></div>
    </div>
</x-app-layout>
