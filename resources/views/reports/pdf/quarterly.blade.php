<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quarterly Financial Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        .header { border-bottom: 2px solid #0c6d57; padding-bottom: 8px; margin-bottom: 12px; }
        .title { font-size: 16px; font-weight: bold; color: #0c6d57; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 6px; text-align: left; }
        th { background: #f8fafc; text-transform: uppercase; font-size: 10px; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Quarterly Financial Report</div>
        <div>Elite Visionaries of Humayingan SLP Association</div>
        <div>Generated: {{ $generatedAt->format('M d, Y h:i A') }}</div>
    </div>

    <table>
        <tbody>
            <tr><th>Period</th><td>{{ $report['summary']['period'] ?? '' }}</td></tr>
            <tr><th>Total Sales</th><td class="right">PHP {{ number_format((float) ($report['summary']['total_sales'] ?? 0), 2) }}</td></tr>
            <tr><th>Total Collected</th><td class="right">PHP {{ number_format((float) ($report['summary']['total_collected'] ?? 0), 2) }}</td></tr>
            <tr><th>Total Expenses</th><td class="right">PHP {{ number_format((float) ($report['summary']['total_expenses'] ?? 0), 2) }}</td></tr>
            <tr><th>Net Result</th><td class="right">PHP {{ number_format((float) ($report['summary']['net_result'] ?? 0), 2) }}</td></tr>
        </tbody>
    </table>
</body>
</html>
