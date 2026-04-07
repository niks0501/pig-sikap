<x-admin-layout
    title="Dashboard"
    subtitle="Overview of user access, role setup, and recent account activities."
    breadcrumb="Dashboard"
>
    @php
        $vueProps = [
            'summary' => $summary,
            'recentLogins' => $recentLogins,
            'recentActivityLogs' => $recentActivityLogs,
            'usersRoute' => route('admin.users.index'),
            'rolesRoute' => route('admin.roles.index'),
            'logsRoute' => route('admin.activity-logs.index'),
        ];
    @endphp

    <div
        data-vue-component="admin-dashboard"
        data-props='@json($vueProps)'
    ></div>
</x-admin-layout>
