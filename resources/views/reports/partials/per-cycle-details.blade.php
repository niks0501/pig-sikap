@if (!empty($report['expense_rows']))
@php $tableProps = json_encode(['type' => $type, 'rows' => $report['expense_rows'], 'summary' => $report['summary'] ?? []]); @endphp
<div data-vue-component="report-table" data-props="{{ $tableProps }}"></div>
@endif

@if (!empty($report['sales_rows']))
<h4 class="mt-8 mb-3 text-sm font-bold text-gray-700 border-b pb-2">Sales Records</h4>
<div data-vue-component="report-table" data-props="{{ json_encode(['type' => 'per-cycle-sales', 'rows' => $report['sales_rows'], 'summary' => $report['summary'] ?? []]) }}"></div>
@endif

@if (!empty($report['expense_by_category']))
<h4 class="mt-8 mb-3 text-sm font-bold text-gray-700 border-b pb-2">Expense by Category</h4>
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500">Category</th>
                <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-500">Amount</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach ($report['expense_by_category'] as $cat => $amt)
            <tr>
                <td class="px-4 py-2 font-medium text-gray-900">{{ $cat }}</td>
                <td class="px-4 py-2 text-right tabular-nums text-gray-700">PHP {{ number_format((float) $amt, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if (!empty($report['health_incidents']))
<h4 class="mt-8 mb-3 text-sm font-bold text-gray-700 border-b pb-2">Health Incidents</h4>
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500">Date</th>
                <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500">Type</th>
                <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-500">Affected</th>
                <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500">Cause</th>
                <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500">Remarks</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach ($report['health_incidents'] as $inc)
            <tr>
                <td class="px-4 py-2 text-gray-900">{{ $inc['date_reported'] ?? '' }}</td>
                <td class="px-4 py-2 text-gray-900">{{ $inc['incident_type'] ?? '' }}</td>
                <td class="px-4 py-2 text-right tabular-nums text-gray-700">{{ $inc['affected_count'] ?? 0 }}</td>
                <td class="px-4 py-2 text-gray-700">{{ $inc['suspected_cause'] ?? '' }}</td>
                <td class="px-4 py-2 text-gray-700">{{ $inc['remarks'] ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
