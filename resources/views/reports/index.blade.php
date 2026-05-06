<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2">
            <h2 class="text-2xl font-bold text-gray-900">Reports & Analytics</h2>
            <p class="text-sm text-gray-500">Generate formal reports for meetings, compliance, and financial review.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            {{-- One-Tap Quick Reports --}}
            <section class="space-y-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Quick Reports</h3>
                    <p class="text-sm text-gray-500">One-tap generation for your most-used reports. Ready to print.</p>
                </div>
                @php
                $quickButtonsProps = json_encode([
                    'monthlyUrl' => route('reports.quick', ['type' => 'monthly']),
                    'quarterlyUrl' => route('reports.quick', ['type' => 'quarterly']),
                    'perCycleUrl' => route('reports.quick', ['type' => 'per-cycle']),
                    'dswdUrl' => route('reports.quick', ['type' => 'dswd-summary']),
                ]);
                $cyclePickerProps = json_encode([
                    'cycles' => $cycles->map(fn ($c) => [
                        'id' => $c->id,
                        'batch_code' => $c->batch_code,
                        'stage' => $c->stage,
                        'status' => $c->status,
                        'initial_count' => (int) $c->initial_count,
                    ])->values()->all(),
                ]);
                @endphp
                <div data-vue-component="quick-report-buttons" data-props="{{ $quickButtonsProps }}"></div>
                <div data-vue-component="cycle-picker-modal" data-props="{{ $cyclePickerProps }}"></div>
            </section>

            {{-- Detailed Reports Section --}}
            <section class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">All Reports</h3>
                        <p class="text-sm text-gray-500">Livestock, financial, and compliance reports with filter options.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @can('manage-report-schedules')
                            <a href="{{ route('reports.schedules.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-[#0c6d57]/40 hover:text-[#0c6d57]">
                                Manage Schedules
                            </a>
                        @endcan
                        <a href="{{ route('reports.history') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-[#0c6d57]/40 hover:text-[#0c6d57]">
                            View History
                        </a>
                    </div>
                </div>

                @php
                $livestockCards = [
                    ['type' => 'inventory', 'title' => 'Pig Inventory', 'description' => 'Livestock counts, stages, and cycle tracking.', 'icon' => 'inventory', 'lockHint' => 'Secretary or President access'],
                    ['type' => 'health', 'title' => 'Health & Vaccination', 'description' => 'Vaccination tasks, medicine usage, herd health.', 'icon' => 'health', 'lockHint' => 'Secretary or President access'],
                    ['type' => 'mortality', 'title' => 'Mortality', 'description' => 'Livestock loss records with causes and dates.', 'icon' => 'mortality', 'variant' => 'danger', 'lockHint' => 'Secretary or President access'],
                ];
                $financialCards = [
                    ['type' => 'expense', 'title' => 'Expenses', 'description' => 'Feeds, medicine, operations cost breakdown.', 'icon' => 'expense', 'lockHint' => 'Treasurer or President access'],
                    ['type' => 'sales', 'title' => 'Sales', 'description' => 'Sales entries, buyer info, payment tracking.', 'icon' => 'sales', 'lockHint' => 'Treasurer or President access'],
                    ['type' => 'profitability', 'title' => 'Profitability & Sharing', 'description' => 'Cycle net profit, 50/25/25 sharing, snapshots.', 'icon' => 'profitability', 'variant' => 'secondary', 'href' => route('profitability.index')],
                ];
                @endphp

                <h4 class="mt-6 text-sm font-bold uppercase tracking-wider text-gray-400">Livestock Reports</h4>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($livestockCards as $card)
                        @if (Gate::check('generate-report', $card['type'] ?? ''))
                            @php($props = json_encode([
                                'title' => $card['title'],
                                'description' => $card['description'],
                                'icon' => $card['icon'],
                                'href' => $card['href'] ?? route('reports.generate', ['type' => $card['type']]),
                                'variant' => $card['variant'] ?? 'primary',
                            ]))
                        @else
                            @php($props = json_encode([
                                'title' => $card['title'],
                                'description' => $card['description'],
                                'icon' => $card['icon'],
                                'href' => null,
                                'variant' => 'locked',
                                'lockHint' => $card['lockHint'] ?? '',
                            ]))
                        @endif
                        <div data-vue-component="report-card" data-props="{{ $props }}"></div>
                    @endforeach
                </div>

                <h4 class="mt-6 text-sm font-bold uppercase tracking-wider text-gray-400">Financial Reports</h4>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($financialCards as $card)
                        @if (($card['type'] ?? null) === 'profitability' || Gate::check('generate-report', $card['type'] ?? ''))
                            @php($props = json_encode([
                                'title' => $card['title'],
                                'description' => $card['description'],
                                'icon' => $card['icon'],
                                'href' => $card['href'] ?? route('reports.generate', ['type' => $card['type']]),
                                'variant' => $card['variant'] ?? 'primary',
                            ]))
                        @else
                            @php($props = json_encode([
                                'title' => $card['title'],
                                'description' => $card['description'],
                                'icon' => $card['icon'],
                                'href' => null,
                                'variant' => 'locked',
                                'lockHint' => $card['lockHint'] ?? '',
                            ]))
                        @endif
                        <div data-vue-component="report-card" data-props="{{ $props }}"></div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
