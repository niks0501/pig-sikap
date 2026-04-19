<x-admin-layout
    title="Activity Logs"
    subtitle="Track authentication events, account changes, and module-level updates including health workflow actions."
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
