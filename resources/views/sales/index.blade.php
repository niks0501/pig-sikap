<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div
            data-vue-component="sales-index"
            data-props="{{ json_encode([
                'sales' => collect($sales->items())->map(function ($sale) {
                    return [
                        'id' => $sale->id,
                        'batch_id' => $sale->batch_id,
                        'buyer_id' => $sale->buyer_id,
                        'pigs_sold' => $sale->pigs_sold,
                        'sale_method' => $sale->sale_method,
                        'amount' => (float) $sale->amount,
                        'amount_paid' => (float) $sale->amount_paid,
                        'payment_status' => $sale->payment_status,
                        'sale_date' => $sale->sale_date?->toDateString(),
                        'receipt_url' => $sale->receiptUrl(),
                        'buyer' => $sale->buyer ? [
                            'id' => $sale->buyer->id,
                            'name' => $sale->buyer->name,
                            'contact_number' => $sale->buyer->contact_number,
                            'address' => $sale->buyer->address,
                        ] : null,
                        'cycle' => $sale->cycle ? [
                            'id' => $sale->cycle->id,
                            'batch_code' => $sale->cycle->batch_code,
                            'status' => $sale->cycle->status,
                            'stage' => $sale->cycle->stage,
                            'current_count' => $sale->cycle->current_count,
                        ] : null,
                    ];
                })->values(),
                'summary' => $summary,
                'filters' => $filters,
                'cycles' => $cycles->map(fn ($cycle) => [
                    'id' => $cycle->id,
                    'batch_code' => $cycle->batch_code,
                    'current_count' => $cycle->current_count,
                ]),
                'paymentStatusOptions' => $paymentStatusOptions,
                'saleMethodOptions' => $saleMethodOptions,
                'pagination' => [
                    'current_page' => $sales->currentPage(),
                    'last_page' => $sales->lastPage(),
                    'per_page' => $sales->perPage(),
                    'total' => $sales->total(),
                ],
                'routes' => [
                    'index' => route('sales.index'),
                    'create' => route('sales.create'),
                    'show' => route('sales.show', ['sale' => '_ID_']),
                ],
            ]) }}"
        ></div>
    </div>
</x-app-layout>