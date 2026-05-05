<x-app-layout>
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @if (session('status'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif
    <div
        data-vue-component="penalty-detail"
        data-props="{{ json_encode([
            'member' => ['id' => $member->id, 'name' => $member->name],
            'summary' => $summary,
            'penalties' => array_map(fn ($p) => [
                'id' => $p['id'],
                'meeting_title' => $p['meeting']['title'] ?? '',
                'amount' => (float) $p['amount'],
                'status' => $p['status'],
                'reason' => $p['reason'],
                'created_at' => isset($p['created_at']) ? date('M d, Y', strtotime($p['created_at'])) : null,
            ], $penalties),
            'routes' => [
                'index' => route('workflow.penalties.index'),
            ],
        ]) }}"
    ></div>
</div>
</x-app-layout>