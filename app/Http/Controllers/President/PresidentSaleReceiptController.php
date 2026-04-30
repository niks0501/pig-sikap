<?php

namespace App\Http\Controllers\President;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\PigRegistry\SendPigCycleSaleReceiptRequest;
use App\Mail\SaleReceiptMail;
use App\Models\PigCycleSale;
use App\Services\PigRegistry\SaleReceiptService;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PresidentSaleReceiptController extends Controller
{
    use RecordsAuditTrail;

    public function preview(PigCycleSale $sale, SaleReceiptService $saleReceiptService): Response
    {
        $loadedSale = $saleReceiptService->loadSale($sale->id);
        $pdf = $saleReceiptService->buildPdf($loadedSale);

        return response($pdf['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdf['file_name'].'"',
        ]);
    }

    public function download(PigCycleSale $sale, SaleReceiptService $saleReceiptService): Response
    {
        $loadedSale = $saleReceiptService->loadSale($sale->id);
        $pdf = $saleReceiptService->buildPdf($loadedSale);

        return response($pdf['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$pdf['file_name'].'"',
        ]);
    }

    public function send(
        SendPigCycleSaleReceiptRequest $request,
        PigCycleSale $sale,
        SaleReceiptService $saleReceiptService
    ): JsonResponse {
        $loadedSale = $saleReceiptService->loadSale($sale->id);
        $email = trim((string) $request->input('email'));

        try {
            $pdf = $saleReceiptService->buildPdf($loadedSale);

            Mail::to($email)->send(new SaleReceiptMail($loadedSale, $pdf['file_name'], $pdf['content']));

            if ($loadedSale->buyer && $loadedSale->buyer->email === null) {
                $loadedSale->buyer->update([
                    'email' => $email,
                    'updated_by' => $request->user()?->id,
                ]);
            }

            $loadedSale->update([
                'digital_receipt_email' => $email,
                'digital_receipt_status' => 'sent',
                'digital_receipt_sent_at' => now(),
                'digital_receipt_error' => null,
            ]);

            $this->recordAudit(
                $request,
                'sale_receipt_sent',
                'Sent digital receipt for sale #'.$loadedSale->id.' to '.$email.'.',
                'sales_management',
                [
                    'sale_id' => $loadedSale->id,
                    'buyer_id' => $loadedSale->buyer_id,
                    'email' => $email,
                ]
            );

            return response()->json([
                'message' => 'Digital receipt sent successfully.',
                'sale' => $this->salePayload($loadedSale->fresh([
                    'cycle:id,batch_code,status,stage,current_count',
                    'buyer:id,name,email,contact_number,address,notes',
                    'createdBy:id,name',
                    'updatedBy:id,name',
                ])),
            ]);
        } catch (Throwable $exception) {
            $loadedSale->update([
                'digital_receipt_status' => 'failed',
                'digital_receipt_error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Digital receipt could not be sent. Please try again.',
                'error' => $exception->getMessage(),
            ], 422);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function salePayload(PigCycleSale $sale): array
    {
        $saleDate = $sale->sale_date;

        return [
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
            'sale_date' => $saleDate instanceof Carbon ? $saleDate->toDateString() : null,
            'receipt_reference' => $sale->receipt_reference,
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
        ];
    }
}
