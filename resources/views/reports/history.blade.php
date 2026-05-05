<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('reports.index') }}" class="text-gray-500 hover:text-gray-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Report History</h2>
                    <p class="text-sm text-gray-500">Browse and re-download previously generated reports.</p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @php
            $historyProps = json_encode([
                'reports' => collect($reports->items())->map(fn ($r) => [
                    'id' => $r->id,
                    'report_type' => $r->report_type,
                    'format' => $r->format,
                    'cycle_code' => $r->cycle?->batch_code ?? 'N/A',
                    'generated_by' => $r->generator?->name ?? 'System',
                    'generated_at' => $r->generated_at?->format('M d, Y h:i A'),
                    'file_size' => $r->file_size ? round($r->file_size / 1024, 1).' KB' : 'N/A',
                    'status' => $r->status,
                    'download_url' => route('reports.download', ['generatedReport' => $r->id]),
                ])->values()->all(),
                'hasPages' => $reports->hasPages(),
                'currentPage' => $reports->currentPage(),
                'lastPage' => $reports->lastPage(),
                'filters' => $filters,
            ]);
            @endphp
            <div data-vue-component="report-history-table" data-props="{{ $historyProps }}"></div>
        </div>
    </div>
</x-app-layout>
