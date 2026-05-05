<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quarterly Financial Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; margin: 0; padding: 20px; }
        .header { border-bottom: 2px solid #0c6d57; padding-bottom: 8px; margin-bottom: 12px; }
        .header-title { font-size: 16px; font-weight: bold; color: #0c6d57; }
        .header-sub { font-size: 10px; color: #6b7280; }
        .badge { float: right; background: #0c6d5718; padding: 4px 12px; border-radius: 8px; font-size: 10px; font-weight: bold; text-transform: uppercase; color: #0c6d57; }
        .meta { margin-bottom: 12px; font-size: 10px; color: #4b5563; }
        .meta span { font-weight: bold; color: #374151; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 6px; text-align: left; }
        th { background: #f8fafc; text-transform: uppercase; font-size: 10px; }
        .right { text-align: right; }
        .total-row { font-weight: bold; background: #f0fdf4; }
        .summary-table td { padding: 4px 8px; border: none; }
        .summary-table th { background: none; border: none; }
        .signatures { margin-top: 30px; width: 100%; }
        .signatures td { border: none; padding: 8px 0; width: 50%; vertical-align: top; }
        .sig-line { border-bottom: 1px solid #374151; margin-top: 30px; width: 70%; }
        .sig-label { font-size: 10px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <div class="badge">QUARTERLY FINANCIAL REPORT</div>
        <div class="header-title">Elite Visionaries of Humayingan SLP Association</div>
        <div class="header-sub">Brgy. Humayingan, Lian, Batangas &bull; Livelihood Monitoring &amp; Profitability Analytics</div>
    </div>

    @php $summary = $report['summary'] ?? []; @endphp
    <div class="meta">
        <span>Period:</span> {{ $summary['period'] ?? 'N/A' }} &nbsp;&bull;&nbsp;
        <span>Cycle:</span> {{ $filters['cycle_id'] ?? 'All Active' }} &nbsp;&bull;&nbsp;
        <span>Generated:</span> {{ $generatedAt->format('M d, Y h:i A') }}
    </div>

    <table class="summary-table">
        <tr><td><strong>Total Sales</strong></td><td class="right">PHP {{ number_format((float) ($summary['total_sales'] ?? 0), 2) }}</td></tr>
        <tr><td><strong>Total Collected</strong></td><td class="right">PHP {{ number_format((float) ($summary['total_collected'] ?? 0), 2) }}</td></tr>
        <tr><td><strong>Total Expenses</strong></td><td class="right">PHP {{ number_format((float) ($summary['total_expenses'] ?? 0), 2) }}</td></tr>
        <tr class="total-row"><td><strong>Net Result</strong></td><td class="right"><strong>PHP {{ number_format((float) ($summary['net_result'] ?? 0), 2) }}</strong></td></tr>
    </table>

    @if (!empty($report['rows'] ?? []))
    <h4 style="margin-top: 16px; font-size: 12px; color: #0c6d57;">Per-Cycle Breakdown</h4>
    <table>
        <thead>
            <tr>
                <th>Cycle</th>
                <th class="right">Sales</th>
                <th class="right">Collected</th>
                <th class="right">Expenses</th>
                <th class="right">Net</th>
                <th class="right">Pigs Sold</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['rows'] as $row)
                <tr>
                    <td>{{ $row['cycle_code'] ?? '' }}</td>
                    <td class="right">PHP {{ number_format((float) ($row['total_sales'] ?? 0), 2) }}</td>
                    <td class="right">PHP {{ number_format((float) ($row['total_collected'] ?? 0), 2) }}</td>
                    <td class="right">PHP {{ number_format((float) ($row['total_expenses'] ?? 0), 2) }}</td>
                    <td class="right">PHP {{ number_format((float) ($row['net_result'] ?? 0), 2) }}</td>
                    <td class="right">{{ $row['pigs_sold'] ?? 0 }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if (!empty($report['category_breakdown'] ?? []))
    <h4 style="margin-top: 16px; font-size: 12px; color: #0c6d57;">Expense by Category</h4>
    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th class="right">Amount</th>
                <th class="right">%</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['category_breakdown'] as $cat)
                <tr>
                    <td>{{ $cat['category'] ?? '' }}</td>
                    <td class="right">PHP {{ number_format((float) ($cat['amount'] ?? 0), 2) }}</td>
                    <td class="right">{{ $cat['percent'] ?? 0 }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if (!empty($chartImages['quarterlyNet'] ?? null))
        <div style="margin-top: 20px; page-break-inside: avoid;">
            <div style="font-size: 13px; font-weight: bold; color: #0c6d57; margin-bottom: 8px;">Quarterly Financial Summary</div>
            <img src="{{ $chartImages['quarterlyNet'] }}" style="max-width: 100%;">
        </div>
    @endif

    <table class="signatures">
        <tr>
            <td>
                <div class="sig-line"></div>
                <div class="sig-label">{{ $preparedBy ?? '' }}</div>
                <div class="sig-label">Prepared By</div>
            </td>
            <td>
                <div class="sig-line"></div>
                <div class="sig-label">{{ $presidentName ?? 'Association President' }}</div>
                <div class="sig-label">Noted By</div>
            </td>
        </tr>
    </table>
</body>
</html>
