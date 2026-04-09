<x-app-layout>
    @php
        $vueProps = [
            'batch' => $batch,
            'adjustmentTypes' => $adjustmentTypes,
            'adjustmentReasons' => $adjustmentReasons,
            'stages' => $stages,
            'statuses' => $statuses,
            'pigStatuses' => $pigStatuses,
            'sexOptions' => $sexOptions,
            'routes' => [
                'index' => route('batches.index'),
                'edit' => route('batches.edit', $batch),
                'archive' => route('batches.archive', $batch),
                'pigsIndex' => route('batches.pigs.index', $batch),
                'pigsStore' => route('batches.pigs.store', $batch),
                'pigsBase' => route('batches.pigs.index', $batch),
                'adjustmentsStore' => route('batches.adjustments.store', $batch),
                'statusStore' => route('batches.status.store', $batch),
            ],
            'csrfToken' => csrf_token(),
            'statusMessage' => session('status'),
            'errorMessage' => $errors->first(),
        ];
    @endphp

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div data-vue-component="pig-batch-show" data-props='@json($vueProps)'></div>
    </div>
</x-app-layout>
