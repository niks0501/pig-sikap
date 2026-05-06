<x-app-layout>
    @php
        $vueProps = [
            'userName' => Auth::user()->name ?? 'User',
            'roleName' => Auth::user()->role?->name ?? 'User',
            'dataUrl' => route('dashboard.data'),
            'routes' => [
                'canvassesCreate' => route('workflow.canvasses.create'),
                'canvassesIndex' => route('workflow.canvasses.index'),
                'suppliersIndex' => route('workflow.suppliers.index'),
            ],
        ];
    @endphp

    <div
        data-vue-component="canvasser-dashboard"
        data-props='@json($vueProps)'
    ></div>
</x-app-layout>
