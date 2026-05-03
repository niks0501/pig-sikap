<x-app-layout>
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="{{ route('workflow.resolutions.show', $resolution) }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-[#0c6d57] transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Back to Resolution
        </a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Create Withdrawal</h1>
        <p class="text-sm text-gray-500 mt-1">Request a fund withdrawal for {{ $resolution->title }}.</p>
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
        data-vue-component="withdrawal-form"
        data-props="{{ json_encode([
            'resolution' => [
                'id' => $resolution->id,
                'title' => $resolution->title,
                'grand_total' => (float) $resolution->grand_total,
                'total_withdrawn' => (float) $resolution->total_withdrawn,
                'remaining_balance' => (float) $resolution->remaining_balance,
            ],
            'routes' => [
                'store' => route('workflow.withdrawals.store', $resolution),
                'back' => route('workflow.resolutions.show', $resolution),
            ],
            'csrfToken' => csrf_token(),
        ]) }}"
    ></div>
</div>
</x-app-layout>
