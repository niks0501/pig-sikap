<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resolution {{ $resolution->resolution_number ?? 'Draft' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 10.5px; line-height: 1.55; }
        .header { border-bottom: 3px solid #0c6d57; padding-bottom: 10px; margin-bottom: 16px; text-align: center; }
        .association { font-size: 15px; font-weight: bold; color: #0c6d57; }
        .address { color: #6b7280; font-size: 10px; margin-top: 2px; }
        .doc-title { margin-top: 10px; font-size: 20px; font-weight: bold; letter-spacing: 1.2px; color: #111827; }
        .res-number { font-size: 13px; font-weight: bold; color: #0c6d57; margin-top: 4px; }
        .section-title { margin-top: 16px; margin-bottom: 6px; font-size: 12px; font-weight: bold; color: #0c6d57; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        .subtitle { font-size: 10px; color: #6b7280; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        th { color: #374151; font-weight: bold; background: #f8faf9; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { font-size: 10px; }
        .info th { width: 32%; text-align: left; }
        .right { text-align: right; }
        .center { text-align: center; }
        .muted { color: #6b7280; }
        .total-row td, .total-row th { border-top: 2px solid #374151; font-weight: bold; font-size: 11px; }
        .resolution-text { border: 1px solid #e5e7eb; padding: 12px 14px; margin-top: 8px; white-space: pre-line; line-height: 1.7; font-size: 11px; }
        .signature-table { margin-top: 30px; }
        .signature-table td { border-bottom: none; width: 33%; padding: 28px 8px 4px; text-align: center; }
        .signature-line { border-bottom: 1px solid #374151; height: 32px; margin: 0 auto 5px; width: 86%; }
        .sig-name { font-weight: bold; font-size: 10px; }
        .sig-role { font-size: 9px; color: #6b7280; }
        .footer { margin-top: 20px; border-top: 1px solid #e5e7eb; padding-top: 8px; font-size: 8.5px; color: #6b7280; text-align: center; }
        .approval-box { border: 1px solid #0c6d57; background: #e7f5f0; padding: 8px 12px; margin-top: 12px; }
        .approval-box strong { color: #0c6d57; }
    </style>
</head>
<body>
    @php
        $currencyPrefix = 'PHP ';
        $formatMoney = fn ($value) => $currencyPrefix . number_format((float) $value, 2);
        $formatDate = fn ($date) => $date ? $date->format('M d, Y') : 'N/A';
        $formatDateTime = fn ($date) => $date ? $date->format('M d, Y h:i A') : 'N/A';
    @endphp

    {{-- Header --}}
    <div class="header">
        <div class="association">{{ $associationName }}</div>
        <div class="address">{{ $associationAddress }}</div>
        <div class="doc-title">RESOLUTION</div>
        <div class="res-number">{{ $resolution->resolution_number ?? 'DRAFT' }}</div>
    </div>

    {{-- Resolution Details --}}
    <div class="section-title">Resolution Details</div>
    <table class="info">
        <tr><th>Title</th><td>{{ $resolution->title }}</td></tr>
        <tr><th>Status</th><td>{{ ucfirst(str_replace('_', ' ', $resolution->status)) }}</td></tr>
        <tr><th>Created By</th><td>{{ $resolution->creator?->name ?? 'N/A' }}</td></tr>
        <tr><th>Created Date</th><td>{{ $formatDateTime($resolution->created_at) }}</td></tr>
        @if ($resolution->approval_deadline)
            <tr><th>Approval Deadline</th><td>{{ $formatDate($resolution->approval_deadline) }}</td></tr>
        @endif
    </table>

    {{-- Resolution Text --}}
    @if ($resolution->description)
        <div class="section-title">Resolution Text</div>
        <div class="resolution-text">{{ $resolution->description }}</div>
    @endif

    {{-- Meeting Details --}}
    @if ($meeting)
        <div class="section-title">Meeting Details</div>
        <table class="info">
            <tr><th>Meeting Title</th><td>{{ $meeting->title }}</td></tr>
            <tr><th>Meeting Date</th><td>{{ $formatDate($meeting->date) }}</td></tr>
            @if ($meeting->location)
                <tr><th>Location</th><td>{{ $meeting->location }}</td></tr>
            @endif
            @if ($meeting->agenda)
                <tr><th>Agenda</th><td>{{ $meeting->agenda }}</td></tr>
            @endif
        </table>
    @endif

    {{-- Budget Line Items --}}
    @if ($includeBudgetSummary && $lineItems->isNotEmpty())
        <div class="section-title">Budget Line Items</div>
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
                @foreach ($lineItems as $item)
                    <tr>
                        <td>{{ $item->category }}</td>
                        <td>{{ $item->description }}</td>
                        <td class="right">{{ number_format((float) $item->quantity, 2) }}</td>
                        <td>{{ $item->unit }}</td>
                        <td class="right">{{ $formatMoney($item->unit_cost) }}</td>
                        <td class="right">{{ $formatMoney($item->total) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <th colspan="5" class="right">Grand Total</th>
                    <td class="right" style="color: #0c6d57;">{{ $formatMoney($totalAmount) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    {{-- Approval Status --}}
    <div class="approval-box">
        <strong>Approval Status:</strong>
        Resolution Date: {{ $documentDate }}.
        This document was generated as an official resolution record.
    </div>

    {{-- Signature Blocks --}}
    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-line"></div>
                <div class="sig-name">{{ $resolution->creator?->name ?? 'Secretary' }}</div>
                <div class="sig-role">Prepared by</div>
            </td>
            <td>
                <div class="signature-line"></div>
                <div class="sig-name">Treasurer</div>
                <div class="sig-role">Checked by</div>
            </td>
            <td>
                <div class="signature-line"></div>
                <div class="sig-name">President</div>
                <div class="sig-role">Approved by</div>
            </td>
        </tr>
    </table>

    {{-- Member Signature Section --}}
    <div class="section-title" style="margin-top: 24px;">Member Signatures</div>
    <p class="muted" style="font-size: 9px; margin-bottom: 8px;">
        This resolution requires 75% member approval. Members who approve must sign below.
    </p>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 30%;">Member Name</th>
                <th style="width: 25%;">Signature</th>
                <th style="width: 20%;">Date</th>
                <th style="width: 20%;">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @for ($i = 1; $i <= 20; $i++)
                <tr>
                    <td>{{ $i }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endfor
        </tbody>
    </table>

    {{-- Footer --}}
    <div class="footer">
        Generated by Pig-Sikap on {{ $formatDateTime($generatedAt) }}.
        Resolution {{ $resolution->resolution_number ?? 'Draft' }} •
        {{ $associationName }}
    </div>
</body>
</html>
