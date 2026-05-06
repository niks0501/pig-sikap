<!-- Shared 3-signature block for preview pages: Prepared By / Treasurer / President -->
<div class="border-t pt-6">
    <p class="mb-5 text-xs font-semibold uppercase tracking-wider text-gray-400">Signatures</p>
    <div class="grid gap-8 text-center sm:grid-cols-3">
        <div>
            <div class="mx-auto mb-2 h-10 w-full max-w-[200px] border-b-2 border-gray-700"></div>
            <p class="text-sm font-semibold text-gray-900">{{ $preparedBy ?? auth()->user()?->name ?? '' }}</p>
            <p class="text-xs uppercase tracking-widest text-gray-500">Prepared By</p>
        </div>
        <div>
            <div class="mx-auto mb-2 h-10 w-full max-w-[200px] border-b-2 border-gray-700"></div>
            <p class="text-sm font-semibold text-gray-900">{{ $treasurerName ?? 'Association Treasurer' }}</p>
            <p class="text-xs uppercase tracking-widest text-gray-500">Treasurer</p>
        </div>
        <div>
            <div class="mx-auto mb-2 h-10 w-full max-w-[200px] border-b-2 border-gray-700"></div>
            <p class="text-sm font-semibold text-gray-900">{{ $presidentName ?? 'Association President' }}</p>
            <p class="text-xs uppercase tracking-widest text-gray-500">President</p>
        </div>
    </div>
</div>
