<x-app-layout>
    @php
        $vueProps = [
            'userName' => Auth::user()->name ?? 'User',
            'roleName' => Auth::user()->role?->name ?? 'User',
            'overviewUrl' => route('dashboard.data'),
            'routes' => [
                'cyclesIndex' => route('cycles.index'),
                'healthIndex' => route('health.index'),
                'expensesCreate' => route('expenses.create'),
                'salesCreate' => route('sales.create'),
                'resolutionsIndex' => route('workflow.resolutions.index'),
            ],
        ];
    @endphp

    <div
        data-vue-component="overall-dashboard"
        data-props='@json($vueProps)'
    ></div>
</x-app-layout>
