<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DSWD/LGU Compliance Summary</title>
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

        .intro-text { font-size: 10px; color: #4b5563; margin-bottom: 12px; line-height: 1.6; }

        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 6px; text-align: left; }
        th { background: #f8fafc; text-transform: uppercase; font-size: 10px; }
        .right { text-align: right; }
        .total-row { font-weight: bold; background: #f0fdf4; }

        .summary-table td { padding: 4px 8px; border: none; }
        .summary-table th { background: none; border: none; }

        .stat-grid { width: 100%; margin-bottom: 12px; }
        .stat-grid td { padding: 6px 10px; font-size: 10px; border: 1px solid #e5e7eb; vertical-align: top; width: 25%; }
        .stat-value { font-size: 16px; font-weight: bold; color: #0c6d57; }

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
        <div class="badge">DSWD/LGU COMPLIANCE SUMMARY</div>
        <div class="header-title">{{ $s['association_name'] ?? 'Elite Visionaries of Humayingan SLP Association' }}</div>
        <div class="header-sub">{{ $s['association_address'] ?? 'Brgy. Humayingan, Lian, Batangas' }} &bull; Sustainable Livelihood Program</div>
    </div>

    <p class="intro-text">
        This document presents a summary of the livelihood program operations of the
        <strong>{{ $s['association_name'] ?? 'Elite Visionaries of Humayingan SLP Association' }}</strong>
        as of {{ ($generatedAt ?? now())->format('F d, Y') }}. It includes key statistics on pig cycles,
        livestock counts, financial performance, and membership data intended for compliance
        reporting to the Department of Social Welfare and Development (DSWD) and Local
        Government Unit (LGU).
    </p>

    <div class="meta-box">
        <div class="meta-row"><span class="meta-label">Generated:</span><span class="meta-value">{{ ($generatedAt ?? now())->format('F d, Y h:i A') }}</span></div>
        <div class="meta-row"><span class="meta-label">Prepared By:</span><span class="meta-value">{{ $preparedBy ?? 'System' }}</span></div>
        <div class="meta-row"><span class="meta-label">Program:</span><span class="meta-value">Sustainable Livelihood Program (SLP)</span></div>
    </div>

    <div class="section-title">Program Overview</div>
    <table class="stat-grid">
        <tr>
            <td><div class="stat-value">{{ $s['total_members'] ?? 0 }}</div><div>Active Members</div></td>
            <td><div class="stat-value">{{ $s['total_officers'] ?? 0 }}</div><div>Association Officers</div></td>
            <td><div class="stat-value">{{ $s['total_cycles'] ?? 0 }}</div><div>Total Pig Cycles</div></td>
            <td><div class="stat-value">{{ $s['active_cycles'] ?? 0 }}</div><div>Active Cycles</div></td>
        </tr>
    </table>

    <div class="section-title">Livestock Summary</div>
    <table class="summary-table">
        <tr><td><strong>Total Pigs Procured (All Cycles)</strong></td><td class="right">{{ $s['total_initial_pigs'] ?? 0 }}</td></tr>
        <tr><td><strong>Current Active Pigs</strong></td><td class="right">{{ $s['total_current_pigs'] ?? 0 }}</td></tr>
        <tr><td><strong>Total Pigs Sold</strong></td><td class="right">{{ $s['total_pigs_sold'] ?? 0 }}</td></tr>
        <tr><td><strong>Completed Cycles</strong></td><td class="right">{{ $s['completed_cycles'] ?? 0 }}</td></tr>
    </table>

    <div class="section-title">Financial Highlights</div>
    <table class="summary-table">
        <tr><td><strong>Total Sales (All Cycles)</strong></td><td class="right">PHP {{ number_format((float) ($s['total_sales'] ?? 0), 2) }}</td></tr>
        <tr><td><strong>Total Collected</strong></td><td class="right">PHP {{ number_format((float) ($s['total_collected'] ?? 0), 2) }}</td></tr>
        <tr><td><strong>Total Receivables</strong></td><td class="right">PHP {{ number_format((float) ($s['total_receivables'] ?? 0), 2) }}</td></tr>
        <tr><td><strong>Total Expenses</strong></td><td class="right">PHP {{ number_format((float) ($s['total_expenses'] ?? 0), 2) }}</td></tr>
        <tr class="total-row"><td><strong>Net Overall Result</strong></td><td class="right"><strong>PHP {{ number_format((float) ($s['net_overall'] ?? 0), 2) }}</strong></td></tr>
    </table>

    <div class="section-title">Additional Information</div>
    <table class="summary-table">
        <tr><td>Total Unique Buyers Served</td><td class="right">{{ $s['total_buyers'] ?? 0 }}</td></tr>
        <tr><td>Program Start Date</td><td class="right">On file with DSWD</td></tr>
        <tr><td>DSWD Accreditation Status</td><td class="right">SLP Beneficiary Association</td></tr>
    </table>

    @if (!empty($report['sales_by_cycle'] ?? []))
    <div class="section-title">Per-Cycle Financial Performance</div>
    <table>
        <thead>
            <tr>
                <th>Cycle</th>
                <th>Status</th>
                <th class="right">Pigs Sold</th>
                <th class="right">Total Sales</th>
                <th class="right">Collected</th>
                <th class="right">Expenses</th>
                <th class="right">Net</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['sales_by_cycle'] as $row)
                <tr>
                    <td>{{ $row['cycle_code'] ?? '' }}</td>
                    <td>{{ $row['status'] ?? '' }}</td>
                    <td class="right">{{ $row['pigs_sold'] ?? 0 }}</td>
                    <td class="right">PHP {{ number_format((float) ($row['total_sales'] ?? 0), 2) }}</td>
                    <td class="right">PHP {{ number_format((float) ($row['total_collected'] ?? 0), 2) }}</td>
                    <td class="right">PHP {{ number_format((float) ($row['total_expenses'] ?? 0), 2) }}</td>
                    <td class="right">PHP {{ number_format((float) ($row['net'] ?? 0), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if (!empty($report['expense_by_category'] ?? []))
    <div class="section-title">Expense by Category (All Cycles)</div>
    <table>
        <thead>
            <tr><th>Category</th><th class="right">Total Amount</th></tr>
        </thead>
        <tbody>
            @foreach ($report['expense_by_category'] as $cat)
                <tr>
                    <td>{{ $cat['category'] ?? '' }}</td>
                    <td class="right">PHP {{ number_format((float) ($cat['amount'] ?? 0), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if (!empty($chartImages['salesVsExpenses'] ?? null))
        <div style="margin-top: 20px; page-break-inside: avoid;">
            <div style="font-size: 13px; font-weight: bold; color: #0c6d57; margin-bottom: 8px;">Financial Overview</div>
            <img src="{{ $chartImages['salesVsExpenses'] }}" style="max-width: 100%;">
        </div>
    @endif

    <p style="margin-top: 24px; font-size: 10px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 8px;">
        This summary was generated on {{ ($generatedAt ?? now())->format('F d, Y') }} for compliance and documentation purposes.
        For detailed records, please refer to individual cycle reports and financial statements on file.
    </p>

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
