<x-admin-layout
    title="Profile"
    subtitle="Update your account details and password securely."
    breadcrumb="Dashboard / Profile"
>
    @php
        $vueProps = [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'updateProfileUrl' => route('admin.profile.update'),
            'updatePasswordUrl' => route('admin.profile.password'),
        ];
    @endphp

    <div
        data-vue-component="admin-profile"
        data-props='@json($vueProps)'
    ></div>
</x-admin-layout>
