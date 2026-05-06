<x-app-layout>
    @php
        $vueProps = [
            'userName' => Auth::user()->name ?? 'User',
            'roleName' => Auth::user()->role?->name ?? 'User',
            'dataUrl' => route('dashboard.data'),
            'routes' => [
                'meetingsCreate' => route('workflow.meetings.create'),
                'resolutionsCreate' => route('workflow.resolutions.create'),
                'resolutionsIndex' => route('workflow.resolutions.index'),
                'meetingsIndex' => route('workflow.meetings.index'),
                'documentsUpload' => route('workflow.documents.page.upload'),
            ],
        ];
    @endphp

    <div
        data-vue-component="secretary-dashboard"
        data-props='@json($vueProps)'
    ></div>
</x-app-layout>
