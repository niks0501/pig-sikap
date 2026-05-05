<x-app-layout>
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @if (session('status'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif
    <div
        data-vue-component="penalty-list"
        data-props="{{ json_encode([
            'summary' => $summary,
            'penalties' => $penalties->map(fn ($p) => [
                'id' => $p->id,
                'user_name' => $p->user?->name,
                'user_id' => $p->user_id,
                'meeting_title' => $p->meeting?->title,
                'amount' => (float) $p->amount,
                'status' => $p->status,
                'reason' => $p->reason,
                'created_at' => $p->created_at?->format('M d, Y'),
            ])->values(),
            'pagination' => $penalties->toArray(),
            'routes' => [
                'waive' => route('workflow.penalties.waive', '__ID__'),
                'pay' => route('workflow.penalties.pay', '__ID__'),
                'byMember' => route('workflow.penalties.by-member', '__USER__'),
            ],
            'csrfToken' => csrf_token(),
            'permissions' => [
                'canWaive' => auth()->user()->can('waive', \App\Models\AttendancePenalty::class),
                'canMarkPaid' => auth()->user()->can('markPaid', \App\Models\AttendancePenalty::class),
            ],
        ]) }}"
    ></div>
</div>
</x-app-layout>