<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div
            data-vue-component="sales-show"
            data-props="{{ json_encode([
                'sale' => [
                    'id' => $sale->id,
                    'batch_id' => $sale->batch_id,
                    'buyer_id' => $sale->buyer_id,
                    'pigs_sold' => $sale->pigs_sold,
                    'sale_method' => $sale->sale_method,
                    'live_weight_kg' => $sale->live_weight_kg,
                    'price_per_kg' => $sale->price_per_kg,
                    'price_per_head' => $sale->price_per_head,
                    'amount' => (float) $sale->amount,
                    'amount_paid' => (float) $sale->amount_paid,
                    'payment_status' => $sale->payment_status,
                    'sale_date' => $sale->sale_date?->toDateString(),
                    'receipt_reference' => $sale->receipt_reference,
                    'receipt_url' => $sale->receiptUrl(),
                    'notes' => $sale->notes,
                    'digital_receipt_number' => $sale->digital_receipt_number,
                    'digital_receipt_path' => $sale->digital_receipt_path,
                    'digital_receipt_email' => $sale->digital_receipt_email,
                    'digital_receipt_status' => $sale->digital_receipt_status,
                    'digital_receipt_sent_at' => $sale->digital_receipt_sent_at?->toIso8601String(),
                    'digital_receipt_error' => $sale->digital_receipt_error,
                    'buyer' => $sale->buyer ? [
                        'id' => $sale->buyer->id,
                        'name' => $sale->buyer->name,
                        'email' => $sale->buyer->email,
                        'contact_number' => $sale->buyer->contact_number,
                        'address' => $sale->buyer->address,
                        'notes' => $sale->buyer->notes,
                    ] : null,
                    'cycle' => $sale->cycle ? [
                        'id' => $sale->cycle->id,
                        'batch_code' => $sale->cycle->batch_code,
                        'status' => $sale->cycle->status,
                        'stage' => $sale->cycle->stage,
                        'current_count' => $sale->cycle->current_count,
                    ] : null,
                    'created_by_name' => $sale->createdBy?->name,
                    'updated_by_name' => $sale->updatedBy?->name,
                ],
                'routes' => [
                    'index' => route('sales.index'),
                    'update' => route('sales.update', ['sale' => '_ID_']),
                    'receiptPreview' => route('sales.receipt.preview', ['sale' => '_ID_']),
                    'receiptDownload' => route('sales.receipt.download', ['sale' => '_ID_']),
                    'receiptSend' => route('sales.receipt.send', ['sale' => '_ID_']),
                    'profitabilityShow' => $sale->cycle ? route('profitability.show', $sale->cycle) : null,
                ],
                'csrfToken' => csrf_token(),
                'canEditPayment' => $canEditPayment,
                'canEditReceipt' => $canEditReceipt,
                'paymentStatusOptions' => \App\Models\PigCycleSale::PAYMENT_STATUSES,
            ]) }}"
        ></div>
    </div>
</x-app-layout>
