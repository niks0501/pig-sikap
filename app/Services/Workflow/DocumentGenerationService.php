<?php

namespace App\Services\Workflow;

use App\Models\AuditTrail;
use App\Models\Resolution;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

/**
 * Generates PDF and DOCX documents for resolutions using
 * DomPDF for PDF and PHPWord for DOCX.
 */
class DocumentGenerationService
{
    public function __construct(
        private readonly DocumentStorageService $storageService
    ) {}

    /**
     * Generate a PDF for the given resolution.
     *
     * @param  array<string, mixed>  $options
     * @return array{file_path: string, version: int}
     */
    public function generatePdf(Resolution $resolution, array $options = []): array
    {
        $resolution->loadMissing(['meeting', 'lineItems', 'creator', 'approvals']);

        $pdf = Pdf::loadView('pdf.workflow.resolution', [
            'associationName' => 'Elite Visionaries of Humayingan SLP Association',
            'associationAddress' => 'Brgy. Humayingan, Lian, Batangas',
            'resolution' => $resolution,
            'meeting' => $resolution->meeting,
            'lineItems' => $resolution->lineItems,
            'totalAmount' => $resolution->grand_total,
            'includeBudgetSummary' => $options['include_budget_summary'] ?? true,
            'includeMemberList' => $options['include_member_list'] ?? false,
            'documentDate' => $options['document_date'] ?? now()->format('F j, Y'),
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        $pdfContent = $pdf->output();

        $versionNumber = $this->storageService->getNextVersionNumber($resolution);
        $filePath = $this->storageService->storeGeneratedDocument(
            $pdfContent,
            $resolution,
            'pdf',
            $versionNumber,
            ['options' => $options]
        );

        $resolution->update([
            'generated_pdf_path' => $filePath,
            'version' => $versionNumber,
            'workflow_status' => 'generated',
        ]);

        // Assign resolution number if not already assigned
        if (! $resolution->resolution_number) {
            $resolution->update([
                'resolution_number' => Resolution::generateResolutionNumber(),
                'resolution_number_assigned_at' => now(),
            ]);
        }

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'resolution_document_generated',
            'module' => 'workflow',
            'description' => "Generated PDF for resolution #{$resolution->resolution_number} (v{$versionNumber})",
            'context_json' => [
                'resolution_id' => $resolution->id,
                'document_type' => 'pdf',
                'version' => $versionNumber,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
        ]);

        return ['file_path' => $filePath, 'version' => $versionNumber];
    }

    /**
     * Generate a DOCX (editable draft) for the given resolution.
     *
     * @param  array<string, mixed>  $options
     * @return array{file_path: string, version: int}
     */
    public function generateDocx(Resolution $resolution, array $options = []): array
    {
        $resolution->loadMissing(['meeting', 'lineItems', 'creator']);

        $phpWord = new PhpWord();
        $phpWord->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\Style\Language('en-PH'));

        // Document properties
        $properties = $phpWord->getDocInfo();
        $properties->setCreator('Pig-Sikap System');
        $properties->setTitle("Resolution {$resolution->resolution_number}");
        $properties->setDescription($resolution->title);

        // Header section
        $section = $phpWord->addSection([
            'marginTop' => 720,
            'marginBottom' => 720,
            'marginLeft' => 1080,
            'marginRight' => 1080,
        ]);

        // Association header
        $section->addText(
            'Elite Visionaries of Humayingan SLP Association',
            ['bold' => true, 'size' => 14, 'color' => '0C6D57'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );
        $section->addText(
            'Brgy. Humayingan, Lian, Batangas',
            ['size' => 10, 'color' => '6B7280'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );

        $section->addTextBreak(1);

        // Resolution title
        $section->addText(
            'RESOLUTION',
            ['bold' => true, 'size' => 16],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );
        $section->addText(
            $resolution->resolution_number ?? 'Draft',
            ['bold' => true, 'size' => 12, 'color' => '0C6D57'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );

        $section->addTextBreak(1);

        // Resolution title and description
        $section->addText($resolution->title, ['bold' => true, 'size' => 12]);

        if ($resolution->description) {
            $section->addTextBreak(1);
            $section->addText($resolution->description, ['size' => 11]);
        }

        // Meeting details
        if ($resolution->meeting) {
            $section->addTextBreak(1);
            $section->addText('Meeting Details:', ['bold' => true, 'size' => 11]);
            $section->addText("Title: {$resolution->meeting->title}", ['size' => 10]);
            $section->addText("Date: " . $resolution->meeting->date?->format('F j, Y'), ['size' => 10]);
            if ($resolution->meeting->location) {
                $section->addText("Location: {$resolution->meeting->location}", ['size' => 10]);
            }
        }

        // Budget table
        if ($resolution->lineItems->isNotEmpty()) {
            $section->addTextBreak(1);
            $section->addText('Budget Line Items:', ['bold' => true, 'size' => 11]);

            $table = $section->addTable([
                'borderSize' => 6,
                'borderColor' => 'E5E7EB',
                'cellMargin' => 80,
            ]);

            // Table header
            $headerStyle = ['bold' => true, 'size' => 9, 'color' => '374151'];
            $table->addRow();
            $table->addCell(2000)->addText('Category', $headerStyle);
            $table->addCell(3000)->addText('Description', $headerStyle);
            $table->addCell(1000)->addText('Qty', $headerStyle);
            $table->addCell(1000)->addText('Unit', $headerStyle);
            $table->addCell(1500)->addText('Unit Cost', $headerStyle);
            $table->addCell(1500)->addText('Total', $headerStyle);

            // Table rows
            $cellStyle = ['size' => 9];
            foreach ($resolution->lineItems as $item) {
                $table->addRow();
                $table->addCell(2000)->addText($item->category, $cellStyle);
                $table->addCell(3000)->addText($item->description, $cellStyle);
                $table->addCell(1000)->addText(number_format((float) $item->quantity, 2), $cellStyle);
                $table->addCell(1000)->addText($item->unit, $cellStyle);
                $table->addCell(1500)->addText('₱' . number_format((float) $item->unit_cost, 2), $cellStyle);
                $table->addCell(1500)->addText('₱' . number_format((float) $item->total, 2), $cellStyle);
            }

            // Total row
            $table->addRow();
            $table->addCell(8500, ['gridSpan' => 5])->addText('Grand Total', ['bold' => true, 'size' => 9]);
            $table->addCell(1500)->addText('₱' . number_format($resolution->grand_total, 2), ['bold' => true, 'size' => 9, 'color' => '0C6D57']);
        }

        // Signature blocks
        $section->addTextBreak(2);
        $section->addText('Prepared by:', ['size' => 10, 'color' => '6B7280']);
        $section->addTextBreak(2);
        $section->addText('_________________________________', ['size' => 10]);
        $section->addText($resolution->creator?->name ?? 'Secretary', ['bold' => true, 'size' => 10]);

        $section->addTextBreak(1);
        $section->addText('Approved by:', ['size' => 10, 'color' => '6B7280']);
        $section->addTextBreak(2);
        $section->addText('_________________________________', ['size' => 10]);
        $section->addText('President', ['bold' => true, 'size' => 10]);

        // Save to temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'resolution_');
        try {
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempFile);
            $docxContent = file_get_contents($tempFile);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }

        $versionNumber = $this->storageService->getNextVersionNumber($resolution);
        $filePath = $this->storageService->storeGeneratedDocument(
            $docxContent,
            $resolution,
            'docx',
            $versionNumber,
            ['options' => $options]
        );

        $resolution->update(['generated_docx_path' => $filePath]);

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'resolution_document_generated',
            'module' => 'workflow',
            'description' => "Generated editable DOCX for resolution #{$resolution->resolution_number} (v{$versionNumber})",
            'context_json' => [
                'resolution_id' => $resolution->id,
                'document_type' => 'docx',
                'version' => $versionNumber,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
        ]);

        return ['file_path' => $filePath, 'version' => $versionNumber];
    }
}
