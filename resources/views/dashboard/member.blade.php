<x-app-layout>
    @php
        $vueProps = [
            'userName' => Auth::user()->name ?? 'User',
            'roleName' => Auth::user()->role?->name ?? 'User',
            'dataUrl' => route('dashboard.data'),
            'routes' => [],
        ];
    @endphp

    <div
        data-vue-component="member-dashboard"
        data-props='@json($vueProps)'
    ></div>
</x-app-layout>
