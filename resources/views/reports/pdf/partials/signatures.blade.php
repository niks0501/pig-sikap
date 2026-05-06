<!-- Shared PDF signature block: Prepared By / Treasurer / President (3 columns) -->
<table class="signatures">
    <tr>
        <td>
            <div class="sig-line"></div>
            <div class="sig-name">{{ $preparedBy ?? auth()->user()?->name ?? '' }}</div>
            <div class="sig-label">Prepared By</div>
        </td>
        <td>
            <div class="sig-line"></div>
            <div class="sig-name">{{ $treasurerName ?? $treasurer ?? 'Association Treasurer' }}</div>
            <div class="sig-label">Treasurer</div>
        </td>
        <td>
            <div class="sig-line"></div>
            <div class="sig-name">{{ $presidentName ?? $president ?? 'Association President' }}</div>
            <div class="sig-label">President</div>
        </td>
    </tr>
</table>
