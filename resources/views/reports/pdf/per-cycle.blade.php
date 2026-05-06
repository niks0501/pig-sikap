<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Per-Cycle Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; margin: 0; padding: 20px; }
        .header { border-bottom: 2px solid #0c6d57; padding-bottom: 8px; margin-bottom: 12px; }
        .header-title { font-size: 16px; font-weight: bold; color: #0c6d57; }
        .header-sub { font-size: 10px; color: #6b7280; }
        .badge { float: right; background: #0c6d5718; padding: 4px 12px; border-radius: 8px; font-size: 10px; font-weight: bold; text-transform: uppercase; color: #0c6d57; }

        .meta-box { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px 12px; margin-bottom: 12px; }
        .meta-row { font-size: 10px; margin-bottom: 2px; }
        .meta-label { font-weight: bold; color: #374151; display: inline-block; width: 100px; }
        .meta-value { color: #4b5563; }

        .section-title { font-size: 13px; font-weight: bold; color: #0c6d57; margin-top: 18px; margin-bottom: 6px; padding-bottom: 4px; border-bottom: 1px solid #e5e7eb; }

        .info-grid { width: 100%; margin-bottom: 12px; }
        .info-grid td { padding: 4px 8px; font-size: 10px; border: none; vertical-align: top; width: 33%; }

        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 6px; text-align: left; }
        th { background: #f8fafc; text-transform: uppercase; font-size: 10px; }
        .right { text-align: right; }
        .total-row { font-weight: bold; background: #f0fdf4; }

        .summary-table td { padding: 4px 8px; border: none; }
        .summary-table th { background: none; border: none; }

        .signatures { margin-top: 30px; width: 100%; }
        .signatures td { border: none; padding: 8px 0; width: 33%; vertical-align: top; }
        .sig-line { border-bottom: 1px solid #374151; margin-top: 30px; width: 70%; }
        .sig-name { font-size: 10px; font-weight: bold; color: #1f2937; margin-top: 4px; }
        .sig-label { font-size: 10px; color: #6b7280; }
    </style>
</head>
<body>
    @php $s = $report['summary'] ?? []; @endphp

    <div class="header">
        <div class="badge">PER-CYCLE REPORT</div>
        <div class="header-title">Elite Visionaries of Humayingan SLP Association</div>
        <div class="header-sub">Brgy. Humayingan, Lian, Batangas &bull; Livelihood Monitoring &amp; Profitability Analytics</div>
    </div>

    <div class="meta-box">
        <div class="meta-row"><span class="meta-label">Cycle:</span><span class="meta-value">{{ $s['cycle_code'] ?? 'N/A' }}</span></div>
        <div class="meta-row"><span class="meta-label">Status:</span><span class="meta-value">{{ $s['status'] ?? '' }} &mdash; {{ $s['stage'] ?? '' }}</span></div>
        <div class="meta-row"><span class="meta-label">Caretaker:</span><span class="meta-value">{{ $s['caretaker'] ?? '' }}</span></div>
        <div class="meta-row"><span class="meta-label">Generated:</span><span class="meta-value">{{ ($generatedAt ?? now())->format('F d, Y h:i A') }}</span></div>
        <div class="meta-row"><span class="meta-label">Prepared By:</span><span class="meta-value">{{ $preparedBy ?? 'System' }}</span></div>
    </div>

    <div class="section-title">Cycle Overview</div>
    <table class="info-grid">
        <tr>
            <td><strong>Initial Count:</strong> {{ $s['initial_count'] ?? 0 }}</td>
            <td><strong>Current Count:</strong> {{ $s['current_count'] ?? 0 }}</td>
            <td><strong>Date of Purchase:</strong> {{ $s['date_of_purchase'] ?? '' }}</td>
        </tr>
    </table>

    <div class="section-title">Financial Summary</div>
    <table class="summary-table">
        <tr><td><strong>Total Sales</strong></td><td class="right">PHP {{ number_format((float) ($s['total_sales'] ?? 0), 2) }}</td></tr>
        <tr><td><strong>Total Collected</strong></td><td class="right">PHP {{ number_format((float) ($s['total_collected'] ?? 0), 2) }}</td></tr>
        <tr><td><strong>Total Receivables</strong></td><td class="right">PHP {{ number_format((float) ($s['receivables'] ?? 0), 2) }}</td></tr>
        <tr><td><strong>Total Expenses</strong></td><td class="right">PHP {{ number_format((float) ($s['total_expenses'] ?? 0), 2) }}</td></tr>
        <tr class="total-row"><td><strong>Net Result</strong></td><td class="right"><strong>PHP {{ number_format((float) ($s['net_result'] ?? 0), 2) }}</strong></td></tr>
    </table>

    @if (!empty($s['caretaker_share']) || !empty($s['member_share']) || !empty($s['association_share']))
    <div class="section-title">Profit Sharing (50/25/25)</div>
    <table class="summary-table">
        <tr><td>Caretaker Share (50%)</td><td class="right">PHP {{ number_format((float) ($s['caretaker_share'] ?? 0), 2) }}</td></tr>
        <tr><td>Member Share (25%)</td><td class="right">PHP {{ number_format((float) ($s['member_share'] ?? 0), 2) }}</td></tr>
        <tr><td>Association Share (25%)</td><td class="right">PHP {{ number_format((float) ($s['association_share'] ?? 0), 2) }}</td></tr>
    </table>
    @endif

    @if (!empty($report['sales_rows'] ?? []))
    <div class="section-title">Sales Records</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Buyer</th>
                <th class="right">Pigs Sold</th>
                <th class="right">Amount</th>
                <th class="right">Paid</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['sales_rows'] as $row)
                <tr>
                    <td>{{ $row['sale_date'] ?? '' }}</td>
                    <td>{{ $row['buyer'] ?? '' }}</td>
                    <td class="right">{{ $row['pigs_sold'] ?? 0 }}</td>
                    <td class="right">PHP {{ number_format((float) ($row['amount'] ?? 0), 2) }}</td>
                    <td class="right">PHP {{ number_format((float) ($row['amount_paid'] ?? 0), 2) }}</td>
                    <td>{{ $row['payment_status'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if (!empty($report['expense_rows'] ?? []))
    <div class="section-title">Expense Records</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Category</th>
                <th>Item</th>
                <th class="right">Qty</th>
                <th class="right">Unit Cost</th>
                <th class="right">Amount</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['expense_rows'] as $row)
                <tr>
                    <td>{{ $row['expense_date'] ?? '' }}</td>
                    <td>{{ $row['category'] ?? '' }}</td>
                    <td>{{ $row['item_name'] ?? '' }}</td>
                    <td class="right">{{ $row['quantity'] ?? 0 }}</td>
                    <td class="right">PHP {{ number_format((float) ($row['unit_cost'] ?? 0), 2) }}</td>
                    <td class="right">PHP {{ number_format((float) ($row['amount'] ?? 0), 2) }}</td>
                    <td>{{ $row['notes'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if (!empty($report['expense_by_category'] ?? []))
    <div class="section-title">Expense by Category</div>
    <table>
        <thead>
            <tr><th>Category</th><th class="right">Amount</th></tr>
        </thead>
        <tbody>
            @foreach ($report['expense_by_category'] as $cat => $amt)
                <tr><td>{{ $cat }}</td><td class="right">PHP {{ number_format((float) $amt, 2) }}</td></tr>
            @endforeach
        </tbody>
    </table>
    @endif
    @endif

    @if (!empty($report['health_incidents'] ?? []))
    <div class="section-title">Health Incidents</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th class="right">Affected</th>
                <th>Suspected Cause</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['health_incidents'] as $inc)
                <tr>
                    <td>{{ $inc['date_reported'] ?? '' }}</td>
                    <td>{{ $inc['incident_type'] ?? '' }}</td>
                    <td class="right">{{ $inc['affected_count'] ?? 0 }}</td>
                    <td>{{ $inc['suspected_cause'] ?? '' }}</td>
                    <td>{{ $inc['remarks'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if (!empty($chartImages['expenseByCategory'] ?? null))
        <div style="margin-top: 20px; page-break-inside: avoid;">
            <div style="font-size: 13px; font-weight: bold; color: #0c6d57; margin-bottom: 8px;">Expense by Category</div>
            <img src="{{ $chartImages['expenseByCategory'] }}" style="max-width: 100%;">
        </div>
    @endif

    <table class="signatures">
        <tr>
            <td>
                <div class="sig-line"></div>
                <div class="sig-name">{{ $preparedBy ?? '' }}</div>
                <div class="sig-label">Prepared By</div>
            </td>
            <td>
                <div class="sig-line"></div>
                <div class="sig-name">{{ $treasurerName ?? 'Association Treasurer' }}</div>
                <div class="sig-label">Treasurer</div>
            </td>
            <td>
                <div class="sig-line"></div>
                <div class="sig-name">{{ $presidentName ?? 'Association President' }}</div>
                <div class="sig-label">President</div>
            </td>
        </tr>
    </table>
</body>
</html>
