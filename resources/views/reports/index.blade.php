<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2">
            <h2 class="text-2xl font-bold text-gray-900">Reports & Analytics</h2>
            <p class="text-sm text-gray-500">Generate formal reports for meetings, compliance, and financial review.</p>
        </div>
    </x-slot>

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
                    @can('generate-report', 'inventory')
                        @php($props = json_encode(["title" => "Pig Inventory", "description" => "Summary of current livestock counts, stages, and cycle tracking.", "icon" => "inventory", "href" => route('reports.generate', ['type' => 'inventory']), "variant" => "primary"]))
                        <div data-vue-component="report-card" data-props="{{ $props }}"></div>
                    @else
                        @php($props = json_encode(["title" => "Pig Inventory", "description" => "Summary of current livestock counts, stages, and cycle tracking.", "icon" => "inventory", "href" => null, "variant" => "locked", "lockHint" => "Secretary or President access"]))
                        <div data-vue-component="report-card" data-props="{{ $props }}"></div>
                    @endcan
                    @can('generate-report', 'health')
                        @php($props = json_encode(["title" => "Health & Vaccination", "description" => "Vaccination tasks, medicine usage, and herd health status.", "icon" => "health", "href" => route('reports.generate', ['type' => 'health']), "variant" => "primary"]))
                        <div data-vue-component="report-card" data-props="{{ $props }}"></div>
                    @else
                        @php($props = json_encode(["title" => "Health & Vaccination", "description" => "Vaccination tasks, medicine usage, and herd health status.", "icon" => "health", "href" => null, "variant" => "locked", "lockHint" => "Secretary or President access"]))
                        <div data-vue-component="report-card" data-props="{{ $props }}"></div>
                    @endcan
                    @can('generate-report', 'mortality')
                        @php($props = json_encode(["title" => "Mortality", "description" => "Records of livestock loss with causes and timestamps.", "icon" => "mortality", "href" => route('reports.generate', ['type' => 'mortality']), "variant" => "danger"]))
                        <div data-vue-component="report-card" data-props="{{ $props }}"></div>
                    @else
                        @php($props = json_encode(["title" => "Mortality", "description" => "Records of livestock loss with causes and timestamps.", "icon" => "mortality", "href" => null, "variant" => "locked", "lockHint" => "Secretary or President access"]))
                        <div data-vue-component="report-card" data-props="{{ $props }}"></div>
                    @endcan
                </div>
            </section>

            <section class="space-y-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Financial Reports</h3>
                        <p class="text-sm text-gray-500">Expenses, sales, monthly, quarterly, and profitability summaries.</p>
                    </div>
                    @can('manage-report-schedules')
                        <a href="{{ route('reports.schedules.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-[#0c6d57]/40 hover:text-[#0c6d57]">
                            Manage Schedules
                        </a>
                    @endcan
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @can('generate-report', 'expense')
                        @php($props = json_encode(["title" => "Expenses", "description" => "Feeds, medicine, operations, and emergency cost breakdown.", "icon" => "expense", "href" => route('reports.generate', ['type' => 'expense']), "variant" => "primary"]))
                        <div data-vue-component="report-card" data-props="{{ $props }}"></div>
                    @else
                        @php($props = json_encode(["title" => "Expenses", "description" => "Feeds, medicine, operations, and emergency cost breakdown.", "icon" => "expense", "href" => null, "variant" => "locked", "lockHint" => "Treasurer or President access"]))
                        <div data-vue-component="report-card" data-props="{{ $props }}"></div>
                    @endcan
                    @can('generate-report', 'sales')
                        @php($props = json_encode(["title" => "Sales", "description" => "Sales entries, buyer info, and payment status tracking.", "icon" => "sales", "href" => route('reports.generate', ['type' => 'sales']), "variant" => "primary"]))
                        <div data-vue-component="report-card" data-props="{{ $props }}"></div>
                    @else
                        @php($props = json_encode(["title" => "Sales", "description" => "Sales entries, buyer info, and payment status tracking.", "icon" => "sales", "href" => null, "variant" => "locked", "lockHint" => "Treasurer or President access"]))
                        <div data-vue-component="report-card" data-props="{{ $props }}"></div>
                    @endcan
                    @php($props = json_encode(["title" => "Profitability & Sharing", "description" => "Cycle net profit, 50/25/25 sharing, and finalized snapshots.", "icon" => "profitability", "href" => route('profitability.index'), "variant" => "secondary"]))
                    <div data-vue-component="report-card" data-props="{{ $props }}"></div>
                    @can('generate-report', 'monthly')
                        @php($props = json_encode(["title" => "Monthly Financial", "description" => "Monthly cashflow, sales, and expense totals.", "icon" => "monthly", "href" => route('reports.generate', ['type' => 'monthly']), "variant" => "primary"]))
                        <div data-vue-component="report-card" data-props="{{ $props }}"></div>
                    @else
                        @php($props = json_encode(["title" => "Monthly Financial", "description" => "Monthly cashflow, sales, and expense totals.", "icon" => "monthly", "href" => null, "variant" => "locked", "lockHint" => "Treasurer or President access"]))
                        <div data-vue-component="report-card" data-props="{{ $props }}"></div>
                    @endcan
                    @can('generate-report', 'quarterly')
                        @php($props = json_encode(["title" => "Quarterly Financial", "description" => "Three-month aggregated business summaries.", "icon" => "quarterly", "href" => route('reports.generate', ['type' => 'quarterly']), "variant" => "primary"]))
                        <div data-vue-component="report-card" data-props="{{ $props }}"></div>
                    @else
                        @php($props = json_encode(["title" => "Quarterly Financial", "description" => "Three-month aggregated business summaries.", "icon" => "quarterly", "href" => null, "variant" => "locked", "lockHint" => "Treasurer or President access"]))
                        <div data-vue-component="report-card" data-props="{{ $props }}"></div>
                    @endcan
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
