<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Preview: {{ ucfirst($type ?? 'report') }} Report</h2>
            <p class="text-sm text-gray-500">Review the report below. Print or download when ready.</p>
        </div>
    </x-slot>

    <style>
        @media print {
            body { background: white !important; }
            header, nav, .no-print, .sticky-toolbar { display: none !important; }
            #printable-report {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
                border-radius: 0 !important;
            }
            #printable-report .report-inner { padding: 0 !important; }
            @page { size: A4 portrait; margin: 1.5cm; }
        }
        @media screen {
            #printable-report {
                background: white;
                box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 16px rgba(0,0,0,0.04);
            }
        }
    </style>

    <div class="py-10">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            {{-- Sticky toolbar --}}
            <div class="sticky-toolbar sticky top-4 z-30 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-200 bg-white/90 px-4 py-3 shadow-sm backdrop-blur no-print">
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('reports.index') }}" class="inline-flex min-h-[44px] items-center gap-1.5 rounded-xl border border-gray-200 px-3 text-sm font-semibold text-gray-600 hover:bg-gray-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        Back to Reports
                    </a>
                    <a href="{{ route('reports.generate', ['type' => $type]) }}" class="inline-flex min-h-[44px] items-center gap-1.5 rounded-xl border border-gray-200 px-3 text-sm font-semibold text-gray-600 hover:bg-gray-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" stroke-width="2" /></svg>
                    Adjust Filters
                </a>
                </div>
                <span class="text-xs text-gray-500">
                    {{ $report['summary']['period'] ?? ($filters['date_range'] ?? '') }}
                    @if (!empty($cycleName)) &middot; {{ $cycleName }} @endif
                </span>
                <div class="flex flex-wrap items-center gap-2">
                    <button onclick="window.print()" class="inline-flex min-h-[44px] items-center gap-2 rounded-xl bg-[#0c6d57] px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0a5a48]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2" stroke-width="2" /><path d="M17 9V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4" stroke-width="2" /><rect x="7" y="13" width="10" height="8" rx="1" stroke-width="2" /></svg>
                        Print
                    </button>
                    <a href="{{ $pdfUrl }}" class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border border-[#0c6d57]/30 bg-white px-5 text-sm font-semibold text-[#0c6d57] transition hover:bg-[#0c6d57]/5">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2" /></svg>
                        Download PDF
                    </a>
                    <a href="{{ $csvUrl }}" class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:border-gray-300">
                        CSV
                    </a>
                </div>
            </div>

            {{-- The printable report container --}}
            <div id="printable-report" class="mx-auto w-full max-w-[210mm] rounded-2xl border border-gray-200">
                <div class="report-inner p-6 sm:p-10">
                    @include('reports.partials.letterhead', ['title' => ucfirst($type ?? 'report')])

                    <div class="mt-6 grid gap-3 text-sm text-gray-600 sm:grid-cols-2">
                        <div>
                            <p><span class="font-semibold text-gray-700">Generated:</span> {{ ($generatedAt ?? now())->format('M d, Y h:i A') }}</p>
                            <p><span class="font-semibold text-gray-700">Prepared By:</span> {{ auth()->user()?->name ?? 'System' }}</p>
                        </div>
                        <div>
                            <p><span class="font-semibold text-gray-700">Cycle:</span> {{ $cycleName ?? 'All Active' }}</p>
                            @php
                            $periodDisplay = match ($filters['date_range'] ?? null) {
                                'this_month' => 'This Month ('.now()->format('F Y').')',
                                'last_month' => 'Last Month ('.now()->copy()->subMonth()->format('F Y').')',
                                'this_quarter' => 'This Quarter (Q'.now()->quarter.' '.now()->year.')',
                                'previous_quarter' => 'Previous Quarter',
                                'this_year' => 'This Year ('.now()->year.')',
                                'custom' => ($filters['start_date'] ?? '').' - '.($filters['end_date'] ?? ''),
                                default => $report['summary']['period'] ?? 'N/A',
                            };
                            @endphp
                            <p><span class="font-semibold text-gray-700">Period:</span> {{ $periodDisplay }}</p>
                        </div>
                    </div>

                    <div class="mt-6">
                        @php $summaryProps = json_encode(['type' => $type, 'summary' => $report['summary'] ?? []]); @endphp
                        <div data-vue-component="report-summary-cards" data-props="{{ $summaryProps }}"></div>
                    </div>

                    {{-- Detailed Data Section --}}
                    @php $includeDetails = $filters['include_details'] ?? true; @endphp

                    @if ($includeDetails)
                    @if (!empty($report['rows']))
                    <div class="mt-8">
                        @php $tableProps = json_encode(['type' => $type, 'rows' => $report['rows'] ?? [], 'summary' => $report['summary'] ?? []]); @endphp
                        <div data-vue-component="report-table" data-props="{{ $tableProps }}"></div>
                    </div>
                    @endif

                    @if ($type === 'per-cycle')
                    <div class="mt-8">
                        @include('reports.partials.per-cycle-details')
                    </div>
                    @endif

                    @if ($type === 'dswd-summary')
                    <div class="mt-8">
                        @include('reports.partials.dswd-summary-details')
                    </div>
                    @endif
                    @php $showCharts = $filters['include_charts'] ?? false; @endphp
                    @if ($showCharts)
                    <div class="mt-10">
                        @php $chartProps = json_encode(['charts' => $report['charts'] ?? [], 'periodLabel' => $report['summary']['period'] ?? '']); @endphp
                        <div data-vue-component="report-charts" data-props="{{ $chartProps }}"></div>
                    </div>
                    @endif
                    @endif

                    <div class="mt-10">
                        @include('reports.partials.officer-signatures', [
                            'preparedBy' => auth()->user()?->name ?? '',
                            'treasurerName' => $treasurerName ?? 'Association Treasurer',
                            'presidentName' => $presidentName ?? 'Association President',
                        ])
                    </div>

                    <div class="mt-8 border-t pt-4 text-center text-xs text-gray-400">
                        Generated on {{ ($generatedAt ?? now())->format('F d, Y \a\t h:i A') }} via Pig-Sikap Livelihood Monitoring System
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
