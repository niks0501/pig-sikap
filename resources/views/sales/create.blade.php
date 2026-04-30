<x-app-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center gap-4 mb-6">
            <a href="{{ route('sales.index') }}" class="p-2 -ml-2 rounded-xl text-gray-500 hover:bg-white hover:text-gray-900 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Record Sale</h1>
                <p class="text-sm text-gray-500 mt-1">Add buyer info, sale details, and payment tracking.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                Please review the form and correct the highlighted fields.
            </div>
        @endif

        <div
            data-vue-component="sales-form"
            data-props="{{ json_encode([
                'cycles' => $cycles->map(fn ($cycle) => [
                    'id' => $cycle->id,
                    'batch_code' => $cycle->batch_code,
                    'current_count' => $cycle->current_count,
                ]),
                'buyers' => $buyers->map(fn ($buyer) => [
                    'id' => $buyer->id,
                    'name' => $buyer->name,
                    'email' => $buyer->email,
                    'contact_number' => $buyer->contact_number,
                    'address' => $buyer->address,
                ]),
                'selectedCycleId' => $selectedCycleId,
                'paymentStatusOptions' => $paymentStatusOptions,
                'saleMethodOptions' => $saleMethodOptions,
                'routes' => [
                    'store' => route('sales.store'),
                    'index' => route('sales.index'),
                    'create' => route('sales.create'),
                    'receiptSend' => route('sales.receipt.send', ['sale' => '_ID_']),
                ],
                'csrfToken' => csrf_token(),
                'oldInput' => old(),
                'errors' => $errors->toArray(),
                'flashStatus' => session('status'),
            ]) }}"
        ></div>
    </div>
</x-app-layout>
