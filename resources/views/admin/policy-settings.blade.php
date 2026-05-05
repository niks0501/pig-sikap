<x-app-layout>
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @if (session('status'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif
    <div
        data-vue-component="policy-settings-form"
        data-props="{{ json_encode([
            'settings' => $settings->map(fn ($group, $key) => [
                'group' => $key,
                'items' => $group->map(fn ($s) => [
                    'key' => $s->key,
                    'value' => $s->value,
                    'description' => $s->description,
                    'value_type' => $s->value_type,
                ])->values(),
            ])->values(),
            'routes' => [
                'update' => route('workflow.settings.update'),
                'index' => route('workflow.settings.index'),
            ],
            'csrfToken' => csrf_token(),
        ]) }}"
    ></div>
</div>
</x-app-layout>