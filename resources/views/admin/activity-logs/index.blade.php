<x-admin-layout
    title="Activity Logs"
    subtitle="Track authentication events, user changes, and account administration actions."
    breadcrumb="Dashboard / Activity Logs"
>
    @php
        $vueProps = [
            'fetchUrl' => route('admin.activity-logs.index'),
        ];
    @endphp

    <div
        data-vue-component="admin-activity-logs"
        data-props='@json($vueProps)'
    ></div>
</x-admin-layout>
