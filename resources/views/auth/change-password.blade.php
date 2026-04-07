<x-guest-layout>
    <div class="mb-5 text-center">
        <h1 class="text-2xl font-semibold text-slate-900">Change Temporary Password</h1>
        <p class="mt-2 text-sm text-slate-600">For security, you must update your temporary password before using the system.</p>
    </div>

    @php
        $vueProps = [
            'updateUrl' => route('password.force.update'),
            'redirectLabel' => 'Continue to Dashboard',
        ];
    @endphp

    <div
        data-vue-component="force-password-change"
        data-props='@json($vueProps)'
    ></div>
</x-guest-layout>
