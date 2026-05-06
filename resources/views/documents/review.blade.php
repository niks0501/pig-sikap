<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Document Review</h1>
            <p class="text-sm text-gray-500 mt-1">Review and approve or reject uploaded documents.</p>
        </div>

        <div class="mb-6 flex items-center gap-1 bg-gray-100 rounded-lg p-1 w-fit">
            <a href="{{ route('workflow.documents.page.upload') }}" class="px-4 py-1.5 rounded-md text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-white/50">Upload</a>
            @if (Auth::user()->isSystemAdmin() || Auth::user()->hasRole('president'))
            <a href="{{ route('workflow.documents.page.types') }}" class="px-4 py-1.5 rounded-md text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-white/50">Types</a>
            @endif
            <a href="{{ route('workflow.documents.page.review') }}" class="px-4 py-1.5 rounded-md text-sm font-medium bg-white text-[#0c6d57] shadow-sm">Review</a>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div
                data-vue-component="document-review-list"
                data-props="{{ json_encode([
                    'listUrl' => route('workflow.documents.index'),
                    'summaryUrl' => route('workflow.documents.summary'),
                ]) }}"
            ></div>
        </div>
    </div>
</x-app-layout>