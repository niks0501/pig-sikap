<x-app-layout>
    @php
        $vueProps = [
            'userName' => Auth::user()->name ?? 'User',
            'roleName' => Auth::user()->role?->name ?? 'User',
            'dataUrl' => route('dashboard.data'),
            'routes' => [
                'expensesCreate' => route('expenses.create'),
                'salesCreate' => route('sales.create'),
                'expensesIndex' => route('expenses.index'),
                'salesIndex' => route('sales.index'),
                'profitabilityIndex' => route('profitability.index'),
            ],
        ];
    @endphp

    <div
        data-vue-component="treasurer-dashboard"
        data-props='@json($vueProps)'
    ></div>
</x-app-layout>
