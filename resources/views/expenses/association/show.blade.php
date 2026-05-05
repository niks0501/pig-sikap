<x-app-layout>
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @php
        $expense->load(['supplier:id,name', 'canvass:id,title', 'approvedResolution:id,title,resolution_number', 'withdrawal:id,amount,status', 'createdBy:id,name', 'updatedBy:id,name']);
    @endphp

    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('expenses.association.index') }}" class="p-2 -ml-2 rounded-xl text-gray-500 hover:bg-white hover:text-gray-900 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $expense->item_name }}</h1>
            <p class="text-sm text-gray-500 mt-1">Association expense details</p>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-8 space-y-5">
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Item</p>
                <p class="mt-1 text-sm font-bold text-gray-900">{{ $expense->item_name }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Category</p>
                <p class="mt-1 text-sm font-bold text-gray-900">
                    {{ $expense->categoryLabel() }}
                    @if ($expense->feed_subcategory)
                        <span class="text-xs text-gray-500">· {{ $expense->feedSubcategoryLabel() }}</span>
                    @endif
                </p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Amount</p>
                <p class="mt-1 text-lg font-black text-gray-900">₱{{ number_format($expense->amount, 2) }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Date</p>
                <p class="mt-1 text-sm font-bold text-gray-900">{{ $expense->expense_date?->format('F j, Y') }}</p>
            </div>

            @if ($expense->quantity)
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Quantity</p>
                <p class="mt-1 text-sm font-bold text-gray-900">{{ $expense->quantity }} {{ $expense->unit }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Unit Cost</p>
                <p class="mt-1 text-sm font-bold text-gray-900">₱{{ number_format($expense->unit_cost, 2) }}</p>
            </div>
            @endif

            @if ($expense->fund_source)
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Fund Source</p>
                <p class="mt-1 text-sm font-bold text-gray-900">{{ $expense->fundSourceLabel() }}</p>
            </div>
            @endif

            @if ($expense->receipt_reference)
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Receipt Reference</p>
                <p class="mt-1 text-sm font-bold text-gray-900">{{ $expense->receipt_reference }}</p>
            </div>
            @endif

            @if ($expense->supplier)
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Supplier</p>
                <p class="mt-1 text-sm font-bold text-gray-900">{{ $expense->supplier->name }}</p>
            </div>
            @endif

            @if ($expense->canvass)
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Canvass</p>
                <p class="mt-1 text-sm font-bold text-gray-900">{{ $expense->canvass->title }}</p>
            </div>
            @endif

            @if ($expense->approvedResolution)
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Approved Resolution</p>
                <p class="mt-1 text-sm font-bold text-gray-900">
                    {{ $expense->approvedResolution->resolution_number }} - {{ $expense->approvedResolution->title }}
                </p>
            </div>
            @endif

            @if ($expense->withdrawal)
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Linked Withdrawal</p>
                <p class="mt-1 text-sm font-bold text-gray-900">₱{{ number_format($expense->withdrawal->amount, 2) }} · {{ ucfirst($expense->withdrawal->status) }}</p>
            </div>
            @endif
        </div>

        @if ($expense->notes)
        <div class="border-t border-gray-100 pt-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Notes</p>
            <p class="mt-1 text-sm text-gray-700">{{ $expense->notes }}</p>
        </div>
        @endif

        @if ($expense->receiptUrl())
        <div class="border-t border-gray-100 pt-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Receipt</p>
            <a href="{{ $expense->receiptUrl() }}" target="_blank" class="mt-1 inline-flex items-center gap-2 text-sm font-semibold text-[#0c6d57] hover:text-[#0a5a48]">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                View Receipt
            </a>
        </div>
        @endif

        <div class="border-t border-gray-100 pt-5 flex items-center justify-between">
            <p class="text-xs text-gray-500">
                Recorded by {{ $expense->createdBy?->name ?? 'Unknown' }}
                @if ($expense->updatedBy)
                    · Updated by {{ $expense->updatedBy->name }}
                @endif
            </p>
            <div class="flex gap-2">
                <a href="{{ route('expenses.association.edit', $expense) }}" class="rounded-xl border border-[#0c6d57] bg-white px-4 py-2 text-sm font-bold text-[#0c6d57] transition hover:bg-[#0c6d57]/5">
                    Edit
                </a>
                <form method="POST" action="{{ route('expenses.association.destroy', $expense) }}" onsubmit="return confirm('Delete this association expense?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="rounded-xl border border-rose-300 bg-white px-4 py-2 text-sm font-bold text-rose-700 transition hover:bg-rose-50">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
