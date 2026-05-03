<x-app-layout>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Meeting Minutes</h1>
            <p class="text-sm text-gray-500 mt-1">Record and manage association meeting minutes.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <a href="{{ route('workflow.resolutions.index') }}" class="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 px-4 py-2.5 bg-white border border-[#0c6d57] text-[#0c6d57] font-semibold rounded-xl hover:bg-[#0c6d57]/5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                View Resolutions
            </a>
            <a href="{{ route('workflow.meetings.create') }}" class="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 px-4 py-2.5 bg-[#0c6d57] text-white font-semibold rounded-xl hover:bg-[#0a5a48] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Record New Meeting
            </a>
        </div>
    </div>

    {{-- Status messages --}}
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

    {{-- Vue component for meetings list --}}
    <div
        data-vue-component="meetings-list"
        data-props="{{ json_encode([
            'meetings' => collect($meetings->items())->map(function($meeting) {
                return [
                    'id' => $meeting->id,
                    'title' => $meeting->title,
                    'date' => $meeting->date?->toDateString(),
                    'date_formatted' => $meeting->date?->format('M d, Y'),
                    'location' => $meeting->location,
                    'status' => $meeting->status,
                    'agenda' => $meeting->agenda,
                    'resolutions_count' => $meeting->resolutions_count ?? 0,
                    'present_count' => $meeting->signatories->where('attendance_status', 'present')->count(),
                    'total_attendees' => $meeting->signatories->count(),
                    'creator_name' => $meeting->creator?->name,
                    'has_minutes_file' => (bool) $meeting->minutes_file_path,
                    'show_url' => route('workflow.meetings.show', $meeting),
                ];
            })->values(),
            'pagination' => [
                'current_page' => $meetings->currentPage(),
                'last_page' => $meetings->lastPage(),
                'per_page' => $meetings->perPage(),
                'total' => $meetings->total(),
            ],
            'routes' => [
                'index' => route('workflow.meetings.index'),
                'create' => route('workflow.meetings.create'),
            ],
            'search' => $search,
        ]) }}"
    ></div>
</div>
</x-app-layout>
