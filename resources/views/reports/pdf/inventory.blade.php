<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        .header { border-bottom: 2px solid #0c6d57; padding-bottom: 8px; margin-bottom: 12px; }
        .title { font-size: 16px; font-weight: bold; color: #0c6d57; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 6px; text-align: left; }
        th { background: #f8fafc; text-transform: uppercase; font-size: 10px; }
        .total { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Inventory Report</div>
        <div>Elite Visionaries of Humayingan SLP Association</div>
        <div>Generated: {{ $generatedAt->format('M d, Y h:i A') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Cycle</th>
                <th>Stage</th>
                <th>Status</th>
                <th>Caretaker</th>
                <th>Initial</th>
                <th>Current</th>
                <th>Active</th>
                <th>Sold</th>
                <th>Deceased</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['rows'] ?? [] as $row)
                <tr>
                    <td>{{ $row['cycle_code'] ?? '' }}</td>
                    <td>{{ $row['stage'] ?? '' }}</td>
                    <td>{{ $row['status'] ?? '' }}</td>
                    <td>{{ $row['caretaker'] ?? '' }}</td>
                    <td>{{ $row['initial_count'] ?? 0 }}</td>
                    <td>{{ $row['current_count'] ?? 0 }}</td>
                    <td>{{ $row['active_pigs'] ?? 0 }}</td>
                    <td>{{ $row['sold_pigs'] ?? 0 }}</td>
                    <td>{{ $row['deceased_pigs'] ?? 0 }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
