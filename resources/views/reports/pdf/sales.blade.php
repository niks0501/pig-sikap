<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report</title>
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
        .signatures { margin-top: 30px; width: 100%; }
        .signatures td { border: none; padding: 8px 0; width: 50%; vertical-align: top; }
        .sig-line { border-bottom: 1px solid #374151; margin-top: 30px; width: 70%; }
        .sig-label { font-size: 10px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <div class="badge">SALES REPORT</div>
        <div class="header-title">Elite Visionaries of Humayingan SLP Association</div>
        <div class="header-sub">Brgy. Humayingan, Lian, Batangas &bull; Livelihood Monitoring &amp; Profitability Analytics</div>
    </div>

    @php $summary = $report['summary'] ?? []; @endphp
    <div class="meta">
        <span>Cycle:</span> {{ $filters['cycle_id'] ?? 'All Active' }} &nbsp;&bull;&nbsp;
        <span>Period:</span> {{ $filters['date_range'] ?? 'N/A' }} &nbsp;&bull;&nbsp;
        <span>Status:</span> {{ $filters['payment_status'] ?? 'All' }} &nbsp;&bull;&nbsp;
        <span>Generated:</span> {{ $generatedAt->format('M d, Y h:i A') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Cycle</th>
                <th>Buyer</th>
                <th>Pigs Sold</th>
                <th class="right">Amount</th>
                <th class="right">Paid</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['rows'] ?? [] as $row)
                <tr>
                    <td>{{ $row['sale_date'] ?? '' }}</td>
                    <td>{{ $row['cycle_code'] ?? '' }}</td>
                    <td>{{ $row['buyer'] ?? '' }}</td>
                    <td>{{ $row['pigs_sold'] ?? 0 }}</td>
                    <td class="right">PHP {{ number_format((float) ($row['amount'] ?? 0), 2) }}</td>
                    <td class="right">PHP {{ number_format((float) ($row['amount_paid'] ?? 0), 2) }}</td>
                    <td>{{ ucfirst((string) ($row['payment_status'] ?? '')) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3"><strong>Totals</strong></td>
                <td><strong>{{ $summary['total_pigs_sold'] ?? 0 }}</strong></td>
                <td class="right"><strong>PHP {{ number_format((float) ($summary['total_amount'] ?? 0), 2) }}</strong></td>
                <td class="right"><strong>PHP {{ number_format((float) ($summary['total_paid'] ?? 0), 2) }}</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    @if (!empty($chartImages['salesVsExpenses'] ?? null))
        <div style="margin-top: 20px; page-break-inside: avoid;">
            <div style="font-size: 13px; font-weight: bold; color: #0c6d57; margin-bottom: 8px;">Sales vs Expenses</div>
            <img src="{{ $chartImages['salesVsExpenses'] }}" style="max-width: 100%;">
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
