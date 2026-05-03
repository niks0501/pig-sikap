<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Official Liquidation Report - Withdrawal #{{ $withdrawal->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 10.5px; line-height: 1.45; }
        .header { border-bottom: 3px solid #0c6d57; padding-bottom: 10px; margin-bottom: 12px; text-align: center; }
        .association { font-size: 15px; font-weight: bold; color: #0c6d57; }
        .address { color: #6b7280; font-size: 10px; }
        .report-title { margin-top: 8px; font-size: 18px; font-weight: bold; letter-spacing: .8px; color: #111827; }
        .subtitle { margin-top: 2px; color: #6b7280; font-size: 10px; }
        .section-title { margin-top: 12px; margin-bottom: 5px; font-size: 12px; font-weight: bold; color: #0c6d57; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th, td { padding: 5px 6px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        th { color: #374151; font-weight: bold; background: #f8faf9; }
        .info th { width: 32%; text-align: left; }
        .right { text-align: right; }
        .center { text-align: center; }
        .muted { color: #6b7280; }
        .status { display: inline-block; padding: 2px 6px; border-radius: 3px; background: #e7f5f0; color: #0c6d57; font-weight: bold; }
        .warning { background: #fef3c7; border: 1px solid #d97706; color: #92400e; padding: 7px 9px; margin-top: 8px; }
        .danger { background: #fee2e2; border: 1px solid #dc2626; color: #991b1b; padding: 7px 9px; margin-top: 8px; }
        .total-row td, .total-row th { border-top: 2px solid #374151; font-weight: bold; }
        .remarks { border: 1px solid #e5e7eb; padding: 8px; min-height: 36px; white-space: pre-line; }
        .signature-table td { border-bottom: none; width: 33%; padding: 22px 8px 4px; text-align: center; }
        .signature-line { border-bottom: 1px solid #374151; height: 28px; margin: 0 auto 5px; width: 86%; }
        .footer { margin-top: 16px; border-top: 1px solid #e5e7eb; padding-top: 7px; font-size: 8.5px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
    @php
        // Use ASCII currency text because DomPDF may not render the peso glyph reliably.
        $currencyPrefix = 'PHP ';
        $formatMoney = fn ($value) => $currencyPrefix.number_format((float) $value, 2);
        $formatDate = fn ($date) => $date ? $date->format('M d, Y') : 'N/A';
        $formatDateTime = fn ($date) => $date ? $date->format('M d, Y h:i A') : 'N/A';
    @endphp

    <div class="header">
        <div class="association">{{ $associationName }}</div>
        <div class="address">{{ $associationAddress }}</div>
        <div class="report-title">LIQUIDATION REPORT</div>
        <div class="subtitle">Official withdrawal liquidation record for association and DSWD compliance</div>
    </div>

    @if ($isOverBudget)
        <div class="danger">
            <strong>Over Budget Notice:</strong> Actual expenses exceeded the approved budget by {{ $formatMoney(abs($variance)) }}.
        </div>
    @endif

    <div class="section-title">Resolution Details</div>
    <table class="info">
        <tr><th>Resolution ID</th><td>#{{ $resolution->id }}</td></tr>
        <tr><th>Title</th><td>{{ $resolution->title }}</td></tr>
        <tr><th>Status</th><td><span class="status">{{ str_replace('_', ' ', ucfirst($resolution->status)) }}</span></td></tr>
        <tr><th>Created By</th><td>{{ $resolution->creator?->name ?? 'N/A' }}</td></tr>
        <tr><th>Created Date</th><td>{{ $formatDateTime($resolution->created_at) }}</td></tr>
        @if ($resolution->description)
            <tr><th>Description</th><td>{{ $resolution->description }}</td></tr>
        @endif
    </table>

    <div class="section-title">Meeting Details</div>
    <table class="info">
        <tr><th>Meeting Title</th><td>{{ $meeting?->title ?? 'N/A' }}</td></tr>
        <tr><th>Meeting Date</th><td>{{ $formatDate($meeting?->date) }}</td></tr>
        <tr><th>Location</th><td>{{ $meeting?->location ?? 'N/A' }}</td></tr>
        <tr><th>Agenda</th><td>{{ $meeting?->agenda ?? 'N/A' }}</td></tr>
    </table>

    <div class="section-title">DSWD Approval Details</div>
    <table class="info">
        <tr><th>Status</th><td>{{ $dswdSubmission ? str_replace('_', ' ', ucfirst($dswdSubmission->status)) : 'N/A' }}</td></tr>
        <tr><th>Date Recorded</th><td>{{ $formatDateTime($dswdSubmission?->submitted_at) }}</td></tr>
        <tr><th>Recorded By</th><td>{{ $dswdSubmission?->submitter?->name ?? 'N/A' }}</td></tr>
        <tr><th>Notes</th><td>{{ $dswdSubmission?->notes ?? 'N/A' }}</td></tr>
    </table>

    <div class="section-title">Withdrawal Details</div>
    <table class="info">
        <tr><th>Withdrawal ID</th><td>#{{ $withdrawal->id }}</td></tr>
        <tr><th>Requested By</th><td>{{ $withdrawal->requester?->name ?? 'N/A' }}</td></tr>
        <tr><th>Amount Withdrawn</th><td>{{ $formatMoney($withdrawal->amount) }}</td></tr>
        <tr><th>Bank Account / Cash Reference</th><td>{{ $withdrawal->bank_account ?? 'N/A' }}</td></tr>
        <tr><th>Status</th><td>{{ ucfirst($withdrawal->status) }}</td></tr>
        <tr><th>Requested Date</th><td>{{ $formatDateTime($withdrawal->requested_at) }}</td></tr>
        <tr><th>Completed Date</th><td>{{ $formatDateTime($withdrawal->completed_at) }}</td></tr>
        <tr><th>Notes</th><td>{{ $withdrawal->notes ?? 'N/A' }}</td></tr>
    </table>

    <div class="section-title">Approved Budget Line Items</div>
    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th>Description</th>
                <th class="right">Qty</th>
                <th>Unit</th>
                <th class="right">Unit Cost</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($lineItems as $item)
                <tr>
                    <td>{{ $item->category }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="right">{{ number_format((float) $item->quantity, 2) }}</td>
                    <td>{{ $item->unit }}</td>
                    <td class="right">{{ $formatMoney($item->unit_cost) }}</td>
                    <td class="right">{{ $formatMoney($item->total) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted center">No approved budget line items recorded.</td></tr>
            @endforelse
            <tr class="total-row">
                <th colspan="5" class="right">Approved Budget Total</th>
                <td class="right">{{ $formatMoney($budgetTotal) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Actual Expenses Linked to Withdrawal</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Category</th>
                <th>Particulars</th>
                <th>Receipt / Proof</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($actualExpenses as $expense)
                <tr>
                    <td>{{ $formatDate($expense->expense_date) }}</td>
                    <td>{{ method_exists($expense, 'categoryLabel') ? $expense->categoryLabel() : ucfirst((string) $expense->category) }}</td>
                    <td>{{ $expense->notes ?: 'No description recorded' }}</td>
                    <td>{{ $expense->receipt_path ? 'Attached' : 'No receipt attached' }}</td>
                    <td class="right">{{ $formatMoney($expense->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted center">No actual expenses have been linked to this withdrawal yet.</td></tr>
            @endforelse
            <tr class="total-row">
                <th colspan="4" class="right">Actual Expense Total</th>
                <td class="right">{{ $formatMoney($actualTotal) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Budget vs Actual Comparison</div>
    <table class="info">
        <tr><th>Approved Budget</th><td class="right">{{ $formatMoney($budgetTotal) }}</td></tr>
        <tr><th>Withdrawal Amount</th><td class="right">{{ $formatMoney($withdrawal->amount) }}</td></tr>
        <tr><th>Actual Expenses</th><td class="right">{{ $formatMoney($actualTotal) }}</td></tr>
        <tr class="total-row"><th>{{ $isOverBudget ? 'Over-Budget Amount' : 'Remaining Balance' }}</th><td class="right">{{ $formatMoney(abs($variance)) }}</td></tr>
    </table>

    @if ($actualExpenses->contains(fn ($expense) => blank($expense->receipt_path)))
        <div class="warning">
            <strong>Receipt Note:</strong> Some expenses do not have attached receipt/proof files. Keep manual receipts with this printed report when available.
        </div>
    @endif

    <div class="section-title">Summary / Remarks</div>
    <div class="remarks">{!! nl2br(e($report->summary ?: 'No additional remarks recorded.')) !!}</div>

    <div class="section-title">Prepared, Checked, and Approved By</div>
    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-line"></div>
                <strong>{{ $preparedBy?->name ?? 'Prepared by' }}</strong><br>
                <span class="muted">Prepared by</span>
            </td>
            <td>
                <div class="signature-line"></div>
                <strong>{{ $treasurer?->name ?? 'Treasurer' }}</strong><br>
                <span class="muted">Checked by Treasurer</span>
            </td>
            <td>
                <div class="signature-line"></div>
                <strong>{{ $president?->name ?? 'President' }}</strong><br>
                <span class="muted">Approved by President</span>
            </td>
        </tr>
    </table>

    <div class="footer">
        Generated by Pig-Sikap on {{ $formatDateTime($generatedAt) }}. Liquidation Report #{{ $report->id }} for Withdrawal #{{ $withdrawal->id }}.
    </div>
</body>
</html>
