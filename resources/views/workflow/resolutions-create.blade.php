<x-app-layout>
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="{{ route('workflow.resolutions.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-[#0c6d57] transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Back to Resolutions
        </a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Create Resolution</h1>
        <p class="text-sm text-gray-500 mt-1">Create a resolution from an existing meeting, with budget line-items.</p>
    </div>

    @if ($errors->any())
    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div
        data-vue-component="resolution-form"
        data-props="{{ json_encode([
            'meeting' => $meeting ? [
                'id' => $meeting->id,
                'title' => $meeting->title,
                'date' => $meeting->date?->format('M d, Y'),
                'agenda' => $meeting->agenda,
            ] : null,
            'meetings' => $meetings->map(fn ($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'date' => $m->date?->format('M d, Y'),
            ])->values(),
            'routes' => [
                'store' => route('workflow.resolutions.store'),
                'index' => route('workflow.resolutions.index'),
            ],
            'csrfToken' => csrf_token(),
        ]) }}"
    ></div>
</div>
</x-app-layout>
