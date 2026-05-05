<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2">
            <h2 class="text-2xl font-bold text-gray-900">Reports & Analytics</h2>
            <p class="text-sm text-gray-500">Generate formal reports for meetings, compliance, and financial review.</p>
        </div>
    </x-slot>

    @php
    $livestockCards = [
        ['type' => 'inventory', 'title' => 'Pig Inventory', 'description' => 'Summary of current livestock counts, stages, and cycle tracking.', 'icon' => 'inventory', 'lockHint' => 'Secretary or President access'],
        ['type' => 'health', 'title' => 'Health & Vaccination', 'description' => 'Vaccination tasks, medicine usage, and herd health status.', 'icon' => 'health', 'lockHint' => 'Secretary or President access'],
        ['type' => 'mortality', 'title' => 'Mortality', 'description' => 'Records of livestock loss with causes and timestamps.', 'icon' => 'mortality', 'variant' => 'danger', 'lockHint' => 'Secretary or President access'],
    ];
    $financialCards = [
        ['type' => 'expense', 'title' => 'Expenses', 'description' => 'Feeds, medicine, operations, and emergency cost breakdown.', 'icon' => 'expense', 'lockHint' => 'Treasurer or President access'],
        ['type' => 'sales', 'title' => 'Sales', 'description' => 'Sales entries, buyer info, and payment status tracking.', 'icon' => 'sales', 'lockHint' => 'Treasurer or President access'],
        ['type' => 'profitability', 'title' => 'Profitability & Sharing', 'description' => 'Cycle net profit, 50/25/25 sharing, and finalized snapshots.', 'icon' => 'profitability', 'variant' => 'secondary', 'href' => route('profitability.index')],
        ['type' => 'monthly', 'title' => 'Monthly Financial', 'description' => 'Monthly cashflow, sales, and expense totals.', 'icon' => 'monthly', 'lockHint' => 'Treasurer or President access'],
        ['type' => 'quarterly', 'title' => 'Quarterly Financial', 'description' => 'Three-month aggregated business summaries.', 'icon' => 'quarterly', 'lockHint' => 'Treasurer or President access'],
    ];
    @endphp

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <section class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Livestock Reports</h3>
                        <p class="text-sm text-gray-500">Inventory, health actions, and mortality monitoring.</p>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($livestockCards as $card)
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

            <section class="space-y-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Financial Reports</h3>
                        <p class="text-sm text-gray-500">Expenses, sales, monthly, quarterly, and profitability summaries.</p>
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
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
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
