<x-app-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
            <div class="flex items-center gap-3">
                <a href="{{ route('expenses.index') }}" class="p-2 -ml-2 rounded-xl text-gray-500 hover:bg-white hover:text-gray-900 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Expense Details</h1>
                    <p class="text-sm text-gray-500 mt-1">Recorded on {{ $expense->expense_date?->format('M d, Y') }}</p>
                </div>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('expenses.edit', $expense) }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-gray-200 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    Edit
                </a>
                @if (auth()->user()?->hasRole('president'))
                    <form method="POST" action="{{ route('expenses.destroy', $expense) }}" onsubmit="return confirm('Delete this expense record?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-rose-200 text-sm font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 transition-colors">
                            Delete
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2 bg-white rounded-2xl border border-gray-100 p-6">
                <div class="flex items-center justify-between gap-4 mb-6">
                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-semibold bg-[#0c6d57]/10 text-[#0c6d57] uppercase tracking-wide">
                        {{ $expense->categoryLabel() }}
                    </span>
                    <p class="text-2xl font-black text-gray-900">Php {{ number_format((float) $expense->amount, 2) }}</p>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500 font-semibold">Cycle</dt>
                        <dd class="text-gray-900 mt-1">{{ $expense->cycle?->batch_code ?? 'Unknown cycle' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 font-semibold">Recorded By</dt>
                        <dd class="text-gray-900 mt-1">{{ $expense->createdBy?->name ?? 'System' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 font-semibold">Created At</dt>
                        <dd class="text-gray-900 mt-1">{{ $expense->created_at?->format('M d, Y h:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 font-semibold">Last Updated</dt>
                        <dd class="text-gray-900 mt-1">{{ $expense->updated_at?->format('M d, Y h:i A') }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500 font-semibold">Description / Notes</dt>
                        <dd class="text-gray-900 mt-1 whitespace-pre-line">{{ $expense->notes }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-3">Receipt</h2>

                @if ($expense->receiptUrl())
                    @php($isPdf = str_ends_with(strtolower((string) $expense->receipt_path), '.pdf'))
                    @if ($isPdf)
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
                            PDF receipt uploaded.
                        </div>
                    @else
                        <img src="{{ $expense->receiptUrl() }}" alt="Receipt for expense {{ $expense->id }}" class="w-full rounded-xl border border-gray-200 object-cover" />
                    @endif

                    <a href="{{ $expense->receiptUrl() }}" target="_blank" rel="noopener" class="mt-3 inline-flex w-full justify-center items-center px-4 py-2 rounded-lg bg-[#0c6d57] text-white text-sm font-semibold hover:bg-[#0a5a48] transition-colors">
                        View Receipt
                    </a>
                @else
                    <p class="text-sm text-gray-500">No receipt attached for this expense.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
