<x-app-layout>
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @if (session('status'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif
    <div
        data-vue-component="canvass-form"
        data-props="{{ json_encode([
            'canvass' => isset($canvass) ? [
                'id' => $canvass->id,
                'title' => $canvass->title,
                'canvass_date' => $canvass->canvass_date?->format('Y-m-d'),
                'resolution_id' => $canvass->resolution_id,
                'meeting_id' => $canvass->meeting_id,
                'notes' => $canvass->notes,
                'status' => $canvass->status,
                'items' => $canvass->items->map(fn ($i) => [
                    'id' => $i->id,
                    'description' => $i->description,
                    'specifications' => $i->specifications,
                    'category' => $i->category,
                    'supplier_id' => $i->supplier_id,
                    'quantity' => (float) $i->quantity,
                    'unit' => $i->unit,
                    'unit_cost' => (float) $i->unit_cost,
                    'total' => (float) $i->total,
                ])->values(),
            ] : null,
            'suppliers' => $suppliers->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
            'resolutions' => $resolutions->map(fn ($r) => ['id' => $r->id, 'title' => $r->title, 'resolution_number' => $r->resolution_number]),
            'meetings' => $meetings->map(fn ($m) => ['id' => $m->id, 'title' => $m->title, 'date' => $m->date?->format('M d, Y')]),
            'routes' => [
                'store' => route('workflow.canvasses.store'),
                'update' => isset($canvass) ? route('workflow.canvasses.update', $canvass) : null,
                'index' => route('workflow.canvasses.index'),
            ],
            'csrfToken' => csrf_token(),
            'isEditing' => isset($canvass),
        ]) }}"
    ></div>
</div>
</x-app-layout>