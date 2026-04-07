<x-admin-layout
    title="Role Management"
    subtitle="Review the available roles used by Pig-Sikap account administration."
    breadcrumb="Dashboard / Roles"
>
    @php
        $vueProps = [
            'initialRoles' => $roles,
            'fetchUrl' => route('admin.roles.index'),
        ];
    @endphp

    <div
        data-vue-component="admin-role-management"
        data-props='@json($vueProps)'
    ></div>
</x-admin-layout>
