<!-- Shared PDF metadata box: generation date, prepared by, cycle, period, filter info -->
<div class="meta-box">
    <div class="meta-row">
        <span class="meta-label">Generated:</span>
        <span class="meta-value">{{ ($generatedAt ?? now())->format('F d, Y h:i A') }}</span>
    </div>
    <div class="meta-row">
        <span class="meta-label">Prepared By:</span>
        <span class="meta-value">{{ $preparedBy ?? auth()->user()?->name ?? 'System' }}</span>
    </div>
    @if (!empty($filters['cycle_id']) || !empty($cycleName))
    <div class="meta-row">
        <span class="meta-label">Cycle:</span>
        <span class="meta-value">{{ $cycleName ?? ($filters['cycle_id'] ?? 'N/A') }}</span>
    </div>
    @endif
    <div class="meta-row">
        <span class="meta-label">Period:</span>
        <span class="meta-value">{{ $periodLabel ?? ($filters['date_range'] ?? 'N/A') }}</span>
    </div>
    @if (!empty($extraMeta))
        @foreach ($extraMeta as $label => $value)
        <div class="meta-row">
            <span class="meta-label">{{ $label }}:</span>
            <span class="meta-value">{{ $value }}</span>
        </div>
        @endforeach
    @endif
</div>
