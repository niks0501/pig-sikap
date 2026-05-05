<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('reports.generate', ['type' => $type ?? 'inventory']) }}" class="text-gray-500 hover:text-gray-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Preview: {{ ucfirst($type ?? 'inventory') }} Report</h2>
                    <p class="text-sm text-gray-500">Review the report and export when ready.</p>
                </div>
            </div>
        </div>
    </x-slot>

    <style>
        @media print {
            body * { visibility: hidden; }
            #printable-report, #printable-report * { visibility: visible; }
            #printable-report { position: absolute; inset: 0; width: 100%; box-shadow: none; border: none; }
            @page { size: portrait; margin: 1cm; }
        }
    </style>

    <div class="py-10">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            @php
            $interactivityProps = json_encode([
                'type' => $type,
                'filters' => $filters,
                'previewUrl' => $previewUrl,
                'pdfUrl' => $pdfUrl,
                'csvUrl' => $csvUrl,
                'report' => $report,
            ]);
            @endphp
            <div data-vue-component="report-preview-interactivity" data-props="{{ $interactivityProps }}"></div>

            <div id="printable-report" class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="p-6 sm:p-8">
                    @include('reports.partials.letterhead', ['title' => ucfirst($type ?? 'report')])

                    <div class="mt-6 grid gap-3 text-sm text-gray-600 sm:grid-cols-2">
                        <div>
                            <p><span class="font-semibold text-gray-700">Generated:</span> {{ now()->format('M d, Y h:i A') }}</p>
                            <p><span class="font-semibold text-gray-700">Prepared By:</span> {{ auth()->user()?->name ?? 'System' }}</p>
                        </div>
                        <div>
                            <p><span class="font-semibold text-gray-700">Cycle:</span> {{ $filters['cycle_id'] ?? 'All Active' }}</p>
                            <p><span class="font-semibold text-gray-700">Period:</span> {{ $filters['date_range'] ?? 'Custom' }}</p>
                        </div>
                    </div>

                    <div class="mt-6">
                        @php($summaryProps = json_encode(['type' => $type, 'summary' => $report['summary'] ?? []]))
                        <div data-vue-component="report-summary-cards" data-props="{{ $summaryProps }}"></div>
                    </div>

                    <div class="mt-8">
                        @php($tableProps = json_encode(['type' => $type, 'rows' => $report['rows'] ?? [], 'summary' => $report['summary'] ?? []]))
                        <div data-vue-component="report-table" data-props="{{ $tableProps }}"></div>
                    </div>

                    <div class="mt-10">
                        @php($chartProps = json_encode(['charts' => $report['charts'] ?? []]))
                        <div data-vue-component="report-charts" data-props="{{ $chartProps }}"></div>
                    </div>

                    <div class="mt-10">
                        @php($sigProps = json_encode(['preparedBy' => auth()->user()?->name, 'notedBy' => 'Association President']))
                        <div data-vue-component="signature-block" data-props="{{ $sigProps }}"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
