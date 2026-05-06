<x-app-layout>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Upload Document</h1>
            <p class="text-sm text-gray-500 mt-1">Select a required document type and upload the file for review.</p>
        </div>

        <div class="mb-6 flex items-center gap-1 bg-gray-100 rounded-lg p-1 w-fit">
            <a href="{{ route('workflow.documents.page.upload') }}" class="px-4 py-1.5 rounded-md text-sm font-medium bg-white text-[#0c6d57] shadow-sm">Upload</a>
            @if (Auth::user()->isSystemAdmin() || Auth::user()->hasRole('president'))
            <a href="{{ route('workflow.documents.page.types') }}" class="px-4 py-1.5 rounded-md text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-white/50">Types</a>
            @endif
            <a href="{{ route('workflow.documents.page.review') }}" class="px-4 py-1.5 rounded-md text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-white/50">Review</a>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div
                data-vue-component="document-upload-form"
                data-props="{{ json_encode([
                    'documentTypes' => $documentTypes->map(function($dt) {
                        return [
                            'id' => $dt->id,
                            'name' => $dt->name,
                            'description' => $dt->description,
                            'allowed_file_types' => $dt->allowed_file_types,
                            'max_size_kb' => $dt->max_size_kb,
                        ];
                    }),
                    'uploadUrl' => route('workflow.documents.upload'),
                ]) }}"
            ></div>
        </div>
    </div>
</x-app-layout>