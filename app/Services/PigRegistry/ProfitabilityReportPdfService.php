<?php

namespace App\Services\PigRegistry;

use App\Models\PigCycle;
use App\Models\ProfitabilitySnapshot;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ProfitabilityReportPdfService
{
    public const STORAGE_PATH = 'generated/profitability/';

    /**
     * Build PDF from a finalized profitability snapshot.
     *
     * @return array{file_name: string, content: string}
     */
    public function buildSnapshotPdf(ProfitabilitySnapshot $snapshot): array
    {
        $snapshot->loadMissing([
            'cycle:id,batch_code,status,stage,caretaker_user_id,status',
            'cycle.caretaker:id,name',
            'finalizedBy:id,name',
        ]);

        $cycle = $snapshot->cycle;
        $profitability = $snapshot->toProfitabilitySummary();

        $pdf = Pdf::loadView('pdf.profitability.report', [
            'snapshot' => $snapshot,
            'cycle' => $cycle,
            'profitability' => $profitability,
            'associationName' => 'Elite Visionaries of Humayingan SLP Association',
            'generatedAt' => now(),
            'isSnapshot' => true,
        ])->setPaper('a4', 'portrait');

        $fileName = sprintf(
            'profitability-report-%s-v%d.pdf',
            $cycle->batch_code,
            $snapshot->version_number
        );
        $content = $pdf->output();

        $fullPath = self::STORAGE_PATH.$fileName;
        Storage::disk('public')->put($fullPath, $content);

        return [
            'file_name' => $fileName,
            'content' => $content,
            'stored_path' => $fullPath,
        ];
    }

    /**
     * Build a live preview PDF from a cycle (not yet finalized).
     *
     * @param  array<string, mixed>  $computed
     * @return array{file_name: string, content: string}
     */
    public function buildLivePreviewPdf(PigCycle $cycle, array $computed): array
    {
        $cycle->loadMissing(['caretaker:id,name']);

        $pdf = Pdf::loadView('pdf.profitability.report', [
            'snapshot' => null,
            'cycle' => $cycle,
            'profitability' => $computed,
            'associationName' => 'Elite Visionaries of Humayingan SLP Association',
            'generatedAt' => now(),
            'isSnapshot' => false,
        ])->setPaper('a4', 'portrait');

        $fileName = sprintf(
            'profitability-draft-%s-%s.pdf',
            $cycle->batch_code,
            now()->format('YmdHis')
        );
        $content = $pdf->output();

        $fullPath = self::STORAGE_PATH.$fileName;
        Storage::disk('public')->put($fullPath, $content);

        return [
            'file_name' => $fileName,
            'content' => $content,
            'stored_path' => $fullPath,
        ];
    }
}