<x-app-layout>
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @if (session('status'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif
    <div
        data-vue-component="supplier-list"
        data-props="{{ json_encode([
            'suppliers' => $suppliers->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'contact_person' => $s->contact_person,
                'contact_number' => $s->contact_number,
                'address' => $s->address,
                'notes' => $s->notes,
                'creator_name' => $s->creator?->name,
            ])->values(),
            'pagination' => $suppliers->toArray(),
            'routes' => [
                'store' => route('workflow.suppliers.store'),
                'update' => route('workflow.suppliers.update', '__ID__'),
                'delete' => route('workflow.suppliers.destroy', '__ID__'),
            ],
            'csrfToken' => csrf_token(),
        ]) }}"
    ></div>
</div>
</x-app-layout>