<x-app-layout>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Resolutions</h1>
            <p class="text-sm text-gray-500 mt-1">Track resolution status from draft to finalized withdrawal.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <a href="{{ route('workflow.meetings.index') }}" class="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 px-4 py-2.5 bg-white border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                View Meetings
            </a>
            <a href="{{ route('workflow.resolutions.create') }}" class="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 px-4 py-2.5 bg-[#0c6d57] text-white font-semibold rounded-xl hover:bg-[#0a5a48] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                New Resolution
            </a>
        </div>
    </div>

    @if (session('status'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        {{ session('status') }}
    </div>
    @endif

    <div
        data-vue-component="resolutions-list"
        data-props="{{ json_encode([
            'resolutions' => collect($resolutions->items())->map(function($r) {
                return [
                    'id' => $r->id,
                    'title' => $r->title,
                    'status' => $r->status,
                    'meeting_title' => $r->meeting?->title,
                    'meeting_date' => $r->meeting?->date?->format('M d, Y'),
                    'approvals_count' => $r->approvals_count ?? 0,
                    'creator_name' => $r->creator?->name,
                    'created_at' => $r->created_at?->format('M d, Y'),
                    'show_url' => route('workflow.resolutions.show', $r),
                ];
            })->values(),
            'pagination' => [
                'current_page' => $resolutions->currentPage(),
                'last_page' => $resolutions->lastPage(),
                'per_page' => $resolutions->perPage(),
                'total' => $resolutions->total(),
            ],
            'routes' => [
                'index' => route('workflow.resolutions.index'),
                'create' => route('workflow.resolutions.create'),
            ],
        ]) }}"
    ></div>
</div>
</x-app-layout>
