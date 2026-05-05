<x-app-layout>
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @if (session('status'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif
    <div
        data-vue-component="canvass-detail"
        data-props="{{ json_encode([
            'canvass' => [
                'id' => $canvass->id,
                'title' => $canvass->title,
                'status' => $canvass->status,
                'canvass_date' => $canvass->canvass_date?->format('M d, Y'),
                'notes' => $canvass->notes,
                'creator_name' => $canvass->creator?->name,
                'resolution' => $canvass->resolution ? ['id' => $canvass->resolution->id, 'title' => $canvass->resolution->title, 'resolution_number' => $canvass->resolution->resolution_number] : null,
                'meeting' => $canvass->meeting ? ['id' => $canvass->meeting->id, 'title' => $canvass->meeting->title] : null,
                'items' => $canvass->items->map(fn ($i) => [
                    'id' => $i->id,
                    'description' => $i->description,
                    'specifications' => $i->specifications,
                    'category' => $i->category,
                    'quantity' => (float) $i->quantity,
                    'unit' => $i->unit,
                    'unit_cost' => (float) $i->unit_cost,
                    'total' => (float) $i->total,
                    'is_selected' => $i->is_selected,
                    'supplier' => $i->supplier ? ['id' => $i->supplier->id, 'name' => $i->supplier->name] : null,
                ])->values(),
            ],
            'routes' => [
                'index' => route('workflow.canvasses.index'),
                'edit' => route('workflow.canvasses.edit', $canvass),
                'selectItem' => route('workflow.canvasses.items.select', ['canvass' => $canvass, 'item' => '__ITEM__']),
            ],
            'csrfToken' => csrf_token(),
        ]) }}"
    ></div>
</div>
</x-app-layout>