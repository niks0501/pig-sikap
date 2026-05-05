<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">Audit Trails</h2>
                <p class="text-sm text-gray-500 mt-1">Monitor system activities, user actions, and changes in the logbook.</p>
            </div>
            <!-- Optional Top action if needed -->
            <!--
            <a href="#" class="inline-flex items-center justify-center px-4 py-2.5 bg-white border border-gray-200 text-gray-700 font-medium text-sm rounded-xl hover:bg-gray-50 transition-colors shadow-sm gap-2">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Export Logs
            </a>
            -->
        </div>
    </x-slot>

    <div
        data-vue-component="audit-trail-list"
        data-props='@json(["fetchUrl" => route("audit-trails.json"), "exportUrl" => route("audit-trails.export")])'
    ></div>
</x-app-layout>
