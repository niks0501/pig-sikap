<x-app-layout>
    @php
        $vueProps = [
            'userName' => Auth::user()->name ?? 'User',
            'roleName' => Auth::user()->role?->name ?? 'User',
            'dataUrl' => route('dashboard.data'),
            'routes' => [
                'healthIndex' => route('health.index'),
                'healthCreate' => route('health.create'),
            ],
        ];
    @endphp

    <div
        data-vue-component="caretaker-dashboard"
        data-props='@json($vueProps)'
    ></div>
</x-app-layout>
