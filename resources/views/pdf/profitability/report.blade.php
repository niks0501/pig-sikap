<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isSnapshot ? 'Official' : 'Draft' }} Profitability Report — {{ $cycle->batch_code }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; line-height: 1.5; }
        .header { border-bottom: 3px solid #0c6d57; padding-bottom: 10px; margin-bottom: 12px; }
        .header .title { font-size: 20px; font-weight: bold; color: #0c6d57; }
        .header .subtitle { font-size: 12px; color: #6b7280; }
        .draft-notice { background: #fef3c7; border: 1px solid #d97706; padding: 8px 12px; margin-bottom: 14px; border-radius: 4px; font-size: 11px; color: #92400e; }
        .loss-notice { background: #fee2e2; border: 1px solid #dc2626; padding: 8px 12px; margin-bottom: 14px; border-radius: 4px; font-size: 11px; color: #991b1b; }
        .section-title { margin-top: 14px; margin-bottom: 6px; font-size: 13px; font-weight: bold; color: #0c6d57; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th, td { padding: 5px 6px; border-bottom: 1px solid #e5e7eb; text-align: left; font-size: 11px; }
        th { color: #6b7280; font-weight: 600; width: 40%; }
        td { font-weight: 600; }
        .right { text-align: right; }
        .muted { color: #6b7280; }
        .total-row td { font-weight: bold; font-size: 12px; border-top: 2px solid #374151; }
        .share-table th { width: auto; text-align: center; }
        .share-table td { text-align: center; }
        .signature-block { margin-top: 30px; }
        .signature-row { margin-top: 24px; }
        .signature-row td { border-bottom: none; padding: 12px 8px; vertical-align: top; width: 33%; }
        .signature-line { border-bottom: 1px solid #374151; margin-top: 30px; width: 80%; }
        .footer { margin-top: 24px; font-size: 9px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ $isSnapshot ? 'Official Profitability Report' : 'Draft Profitability Report' }}</div>
        <div>{{ $associationName }}</div>
        <div class="subtitle">Brgy. Humayingan, Lian, Batangas</div>
    </div>

    @if (! $isSnapshot)
        <div class="draft-notice">
            <strong>Draft Report — Live Computation (Not Finalized)</strong><br>
            This report is generated from current live computation values. Values may change as records are updated.
            Finalize through the Profitability module to create an official snapshot for governance use.
        </div>
    @endif

    @if ((float) $profitability['net_profit_or_loss'] < 0)
        <div class="loss-notice">
            <strong>Loss / No Distributable Profit</strong><br>
            This cycle recorded a net loss. All stakeholder shares are ₱0.00. No distributable profit is available.
        </div>
    @endif

    <div class="section-title">Cycle Information</div>
    <table>
        <tr><th>Cycle Code</th><td>{{ $cycle->batch_code }}</td></tr>
        <tr><th>Caretaker</th><td>{{ $cycle->caretaker?->name ?? 'N/A' }}</td></tr>
        <tr><th>Cycle Status</th><td>{{ $cycle->status }}</td></tr>
        <tr><th>Cycle Stage</th><td>{{ $cycle->stage }}</td></tr>
        @if ($snapshot)
            <tr><th>Snapshot Number</th><td>#{{ $snapshot->snapshot_number }}</td></tr>
            <tr><th>Version</th><td>Version {{ $snapshot->version_number }}</td></tr>
            <tr><th>Date Finalized</th><td>{{ $snapshot->finalized_at?->format('M d, Y h:i A') }}</td></tr>
            <tr><th>Finalized By</th><td>{{ $snapshot->finalizedBy?->name ?? 'N/A' }}</td></tr>
        @endif
        <tr><th>Report Generated</th><td>{{ $generatedAt->format('M d, Y h:i A') }}</td></tr>
    </table>

    <div class="section-title">Financial Summary</div>
    <table>
        <tr><th>Total Sales (Gross Revenue)</th><td class="right">₱{{ number_format((float) $profitability['total_sales'], 2) }}</td></tr>
        <tr><th>Total Collected</th><td class="right">₱{{ number_format((float) ($profitability['total_collected'] ?? 0), 2) }}</td></tr>
        <tr><th>Receivables (Pending Collection)</th><td class="right">₱{{ number_format((float) ($profitability['receivables'] ?? 0), 2) }}</td></tr>
        <tr><th>Total Expenses</th><td class="right">₱{{ number_format((float) $profitability['total_expenses'], 2) }}</td></tr>
        <tr class="total-row"><th>Net Profit / Loss</th><td class="right">₱{{ number_format((float) $profitability['net_profit_or_loss'], 2) }}</td></tr>
    </table>

    <div class="section-title">Expense Breakdown</div>
    <table>
        @foreach ($profitability['expense_breakdown_rows'] as $row)
            @if ((float) $row['total'] > 0)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td class="right">₱{{ number_format((float) $row['total'], 2) }}</td>
                </tr>
            @endif
        @endforeach
        <tr class="total-row">
            <th>Total Expenses</th>
            <td class="right">₱{{ number_format((float) $profitability['total_expenses'], 2) }}</td>
        </tr>
    </table>

    <div class="section-title">Sales Breakdown</div>
    <table>
        @forelse ($profitability['sales_breakdown_rows'] as $row)
            <tr>
                <td>{{ $row['label'] }}</td>
                <td class="right">₱{{ number_format((float) $row['total'], 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="2" class="muted">No sales breakdown available.</td></tr>
        @endforelse
        <tr class="total-row">
            <th>Gross Revenue</th>
            <td class="right">₱{{ number_format((float) $profitability['total_sales'], 2) }}</td>
        </tr>
    </table>

    <div class="section-title">Profit-Sharing Breakdown</div>
    <p class="muted" style="margin-bottom: 4px;">Association rule: 50% Caretaker, 25% Members, 25% Association Fund</p>
    <p class="muted" style="margin-bottom: 8px;">Computed as: Distributable Profit = max(Net Profit, 0). Shares = Distributable Profit × percentage.</p>
    <table>
        <tr><th>Distributable Profit</th><td class="right">₱{{ number_format((float) $profitability['distributable_profit'], 2) }}</td></tr>
        <tr><th>Caretaker / Nag-alaga (50%)</th><td class="right">₱{{ number_format((float) $profitability['caretaker_share'], 2) }}</td></tr>
        <tr><th>Association Members (25%)</th><td class="right">₱{{ number_format((float) $profitability['member_share'], 2) }}</td></tr>
        <tr><th>Association Fund / Samahan (25%)</th><td class="right">₱{{ number_format((float) ($profitability['association_share'] ?? $profitability['association_fund_share'] ?? 0), 2) }}</td></tr>
    </table>

    @if ((float) $profitability['distributable_profit'] <= 0)
        <div style="margin-top: 10px; font-size: 11px; color: #dc2626;">
            <strong>Note:</strong> Distributable profit is ₱0.00 because net profit is zero or negative. No amount should be distributed to stakeholders.
        </div>
    @endif

    @if ($snapshot && $snapshot->notes)
        <div class="section-title">Finalization Notes</div>
        <p style="font-size: 11px; color: #374151;">{{ $snapshot->notes }}</p>
    @endif

    @if ($snapshot && $snapshot->re_finalize_reason_code)
        <div class="section-title">Re-finalization Reason</div>
        <p style="font-size: 11px; color: #374151;">
            <strong>{{ $snapshot->reFinalizeReasonLabel() }}</strong>
            @if ($snapshot->re_finalize_reason_notes)
                <br>{{ $snapshot->re_finalize_reason_notes }}
            @endif
        </p>
    @endif

    <div class="signature-block">
        <div class="section-title">Approval and Verification</div>
        <table class="signature-row">
            <tr>
                <td style="text-align: center;">
                    <div class="signature-line"></div>
                    <div style="margin-top: 4px; font-size: 11px;">Prepared by</div>
                </td>
                <td style="text-align: center;">
                    <div class="signature-line"></div>
                    <div style="margin-top: 4px; font-size: 11px;">Treasurer</div>
                </td>
                <td style="text-align: center;">
                    <div class="signature-line"></div>
                    <div style="margin-top: 4px; font-size: 11px;">President</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        This {{ $isSnapshot ? 'official' : 'draft' }} profitability report was generated by the Pig-Sikap livelihood monitoring and profitability analytics system for the Elite Visionaries of Humayingan SLP Association.
        @if ($snapshot)
            Snapshot #{{ $snapshot->snapshot_number }} v{{ $snapshot->version_number }}
        @endif
        — Generated on {{ $generatedAt->format('M d, Y h:i A') }}.
    </div>
</body>
</html>