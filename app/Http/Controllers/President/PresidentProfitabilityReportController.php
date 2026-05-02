<?php

namespace App\Http\Controllers\President;

use App\Http\Controllers\Controller;
use App\Models\PigCycle;
use App\Models\ProfitabilitySnapshot;
use App\Services\PigRegistry\ComputeCycleProfitabilityService;
use App\Services\PigRegistry\ProfitabilityReportPdfService;
use Illuminate\Http\Response;

class PresidentProfitabilityReportController extends Controller
{
    public function __construct(
        private readonly ComputeCycleProfitabilityService $computeService,
        private readonly ProfitabilityReportPdfService $reportPdfService,
    ) {}

    public function livePreview(PigCycle $cycle): Response
    {
        $cycle->loadMissing(['caretaker:id,name']);
        $computed = $this->computeService->compute($cycle);

        $pdf = $this->reportPdfService->buildLivePreviewPdf($cycle, $computed);

        return response($pdf['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdf['file_name'].'"',
        ]);
    }

    public function liveDownload(PigCycle $cycle): Response
    {
        $cycle->loadMissing(['caretaker:id,name']);
        $computed = $this->computeService->compute($cycle);

        $pdf = $this->reportPdfService->buildLivePreviewPdf($cycle, $computed);

        return response($pdf['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$pdf['file_name'].'"',
        ]);
    }

    public function snapshotPreview(ProfitabilitySnapshot $snapshot): Response
    {
        $pdf = $this->reportPdfService->buildSnapshotPdf($snapshot);

        return response($pdf['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdf['file_name'].'"',
        ]);
    }

    public function snapshotDownload(ProfitabilitySnapshot $snapshot): Response
    {
        $pdf = $this->reportPdfService->buildSnapshotPdf($snapshot);

        return response($pdf['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$pdf['file_name'].'"',
        ]);
    }
}