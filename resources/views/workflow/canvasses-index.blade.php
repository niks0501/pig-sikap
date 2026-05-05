<x-app-layout>
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @if (session('status'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif
    <div
        data-vue-component="canvass-list"
        data-props="{{ json_encode([
            'canvasses' => $canvasses->map(fn ($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'status' => $c->status,
                'canvass_date' => $c->canvass_date?->format('M d, Y'),
                'item_count' => $c->items->count(),
                'winner_count' => $c->items->where('is_selected', true)->count(),
                'creator_name' => $c->creator?->name,
            ])->values(),
            'pagination' => $canvasses->toArray(),
            'routes' => [
                'create' => route('workflow.canvasses.create'),
                'show' => route('workflow.canvasses.show', '__ID__'),
            ],
        ]) }}"
    ></div>
</div>
</x-app-layout>