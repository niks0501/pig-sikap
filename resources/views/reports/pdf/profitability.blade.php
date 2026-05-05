<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profitability Report</title>
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
        <div class="title">Profitability Report</div>
        <div>Elite Visionaries of Humayingan SLP Association</div>
        <div>Generated: {{ $generatedAt->format('M d, Y h:i A') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Cycle</th>
                <th>Status</th>
                <th>Caretaker</th>
                <th class="right">Gross Income</th>
                <th class="right">Expenses</th>
                <th class="right">Net</th>
                <th>Finalized</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['rows'] ?? [] as $row)
                <tr>
                    <td>{{ $row['cycle_code'] ?? '' }}</td>
                    <td>{{ $row['status'] ?? '' }}</td>
                    <td>{{ $row['caretaker'] ?? '' }}</td>
                    <td class="right">PHP {{ number_format((float) ($row['gross_income'] ?? 0), 2) }}</td>
                    <td class="right">PHP {{ number_format((float) ($row['total_expenses'] ?? 0), 2) }}</td>
                    <td class="right">PHP {{ number_format((float) ($row['net_profit_or_loss'] ?? 0), 2) }}</td>
                    <td>{{ ($row['is_finalized'] ?? false) ? 'Yes' : 'No' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
