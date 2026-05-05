<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mortality Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        .header { border-bottom: 2px solid #0c6d57; padding-bottom: 8px; margin-bottom: 12px; }
        .title { font-size: 16px; font-weight: bold; color: #0c6d57; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 6px; text-align: left; }
        th { background: #f8fafc; text-transform: uppercase; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Mortality Report</div>
        <div>Elite Visionaries of Humayingan SLP Association</div>
        <div>Generated: {{ $generatedAt->format('M d, Y h:i A') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Cycle</th>
                <th>Affected</th>
                <th>Suspected Cause</th>
                <th>Reported By</th>
                <th>Media Path</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['rows'] ?? [] as $row)
                <tr>
                    <td>{{ $row['date_reported'] ?? '' }}</td>
                    <td>{{ $row['cycle_code'] ?? '' }}</td>
                    <td>{{ $row['affected_count'] ?? 0 }}</td>
                    <td>{{ $row['suspected_cause'] ?? '' }}</td>
                    <td>{{ $row['reported_by'] ?? '' }}</td>
                    <td>{{ $row['media_path'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
