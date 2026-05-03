<x-app-layout>
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="{{ route('workflow.meetings.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-[#0c6d57] transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Back to Meetings
        </a>
    </div>

    @if (session('status'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        {{ session('status') }}
    </div>
    @endif

    <div
        data-vue-component="meeting-detail"
        data-props="{{ json_encode([
            'meeting' => [
                'id' => $meeting->id,
                'title' => $meeting->title,
                'date' => $meeting->date?->toDateString(),
                'date_formatted' => $meeting->date?->format('F d, Y'),
                'location' => $meeting->location,
                'agenda' => $meeting->agenda,
                'minutes_summary' => $meeting->minutes_summary,
                'minutes_file_url' => $meeting->minutesFileUrl(),
                'status' => $meeting->status,
                'creator_name' => $meeting->creator?->name,
                'created_at' => $meeting->created_at?->format('M d, Y h:i A'),
            ],
            'attendees' => $meeting->signatories->map(fn ($s) => [
                'id' => $s->id,
                'user_id' => $s->user_id,
                'name' => $s->user?->name,
                'role' => $s->user?->role?->name,
                'attendance_status' => $s->attendance_status,
            ])->values(),
            'resolutions' => $meeting->resolutions->map(fn ($r) => [
                'id' => $r->id,
                'title' => $r->title,
                'status' => $r->status,
                'approvals_count' => $r->approvals_count ?? 0,
                'show_url' => route('workflow.resolutions.show', $r),
            ])->values(),
            'routes' => [
                'createResolution' => route('workflow.resolutions.create', ['meeting_id' => $meeting->id]),
                'meetingsIndex' => route('workflow.meetings.index'),
            ],
        ]) }}"
    ></div>
</div>
</x-app-layout>
