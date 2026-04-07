<x-admin-layout
    title="User Management"
    subtitle="Create, update, and control user access across organization roles."
    breadcrumb="Dashboard / Users"
>
    @php
        $vueProps = [
            'roles' => $roles,
            'fetchUrl' => route('admin.users.index'),
            'storeUrl' => route('admin.users.store'),
            'toggleStatusBaseUrl' => url('/admin/users'),
            'resetPasswordBaseUrl' => url('/admin/users'),
        ];
    @endphp

    <div
        data-vue-component="admin-user-management"
        data-props='@json($vueProps)'
    ></div>
</x-admin-layout>
