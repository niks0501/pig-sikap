<?php

namespace App\Services\PigRegistry;

use App\Models\PigCycleSale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class SaleReceiptService
{
    public function loadSale(int $saleId): PigCycleSale
    {
        return PigCycleSale::query()->with([
            'cycle:id,batch_code,status,stage,current_count',
            'buyer:id,name,email,contact_number,address,notes',
            'createdBy:id,name',
            'updatedBy:id,name',
        ])->findOrFail($saleId);
    }

    public function ensureReceiptNumber(PigCycleSale $sale): PigCycleSale
    {
        if (is_string($sale->digital_receipt_number) && $sale->digital_receipt_number !== '') {
            return $sale;
        }

        $sale->update([
            'digital_receipt_number' => sprintf('PSR-%s-%05d', $sale->sale_date?->format('Y') ?? now()->format('Y'), $sale->id),
        ]);

        return $sale->fresh([
            'cycle:id,batch_code,status,stage,current_count',
            'buyer:id,name,email,contact_number,address,notes',
            'createdBy:id,name',
            'updatedBy:id,name',
        ]);
    }

    /**
     * @return array{file_name: string, content: string}
     */
    public function buildPdf(PigCycleSale $sale): array
    {
        $sale = $this->ensureReceiptNumber($sale);

        $pdf = Pdf::loadView('pdf.sales.receipt', [
            'sale' => $sale,
            'associationName' => 'Elite Visionaries of Humayingan SLP Association',
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        $fileName = sprintf('sale-receipt-%d.pdf', $sale->id);
        $content = $pdf->output();

        Storage::disk('public')->put('generated/sales/'.$fileName, $content);

        $sale->update([
            'digital_receipt_path' => 'generated/sales/'.$fileName,
        ]);

        return [
            'file_name' => $fileName,
            'content' => $content,
        ];
    }
}
