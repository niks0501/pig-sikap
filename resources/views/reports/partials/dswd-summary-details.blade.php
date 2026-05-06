@if (!empty($report['sales_by_cycle']))
@php $tableProps = json_encode(['type' => $type, 'rows' => $report['sales_by_cycle'], 'summary' => $report['summary'] ?? []]); @endphp
<div data-vue-component="report-table" data-props="{{ $tableProps }}"></div>
@endif

@if (!empty($report['expense_by_category']))
<h4 class="mt-8 mb-3 text-sm font-bold text-gray-700 border-b pb-2">Expense by Category (All Cycles)</h4>
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500">Category</th>
                <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-500">Total Amount</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach ($report['expense_by_category'] as $cat)
            <tr>
                <td class="px-4 py-2 font-medium text-gray-900">{{ $cat['category'] ?? '' }}</td>
                <td class="px-4 py-2 text-right tabular-nums text-gray-700">PHP {{ number_format((float) ($cat['amount'] ?? 0), 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
