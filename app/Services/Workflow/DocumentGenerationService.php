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
     * Content mirrors the PDF template for consistency.
     *
     * @param  array<string, mixed>  $options
     * @return array{file_path: string, version: int}
     */
    public function generateDocx(Resolution $resolution, array $options = []): array
    {
        $resolution->loadMissing(['meeting', 'lineItems', 'creator', 'approvals']);

        $phpWord = new PhpWord();
        $phpWord->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\Style\Language('en-PH'));

        $docInfo = $phpWord->getDocInfo();
        $docInfo->setCreator('Pig-Sikap System');
        $docInfo->setTitle("Resolution {$resolution->resolution_number}");
        $docInfo->setDescription($resolution->title);

        $section = $phpWord->addSection([
            'marginTop' => 720, 'marginBottom' => 720,
            'marginLeft' => 1080, 'marginRight' => 1080,
        ]);

        // ── Association Header ──
        $section->addText(
            'Elite Visionaries of Humayingan SLP Association',
            ['bold' => true, 'size' => 15, 'color' => '0C6D57'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );
        $section->addText(
            'Brgy. Humayingan, Lian, Batangas',
            ['size' => 10, 'color' => '6B7280'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );
        $section->addTextBreak();

        $section->addText(
            'RESOLUTION',
            ['bold' => true, 'size' => 20],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );
        $section->addText(
            $resolution->resolution_number ?? 'DRAFT',
            ['bold' => true, 'size' => 13, 'color' => '0C6D57'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );
        $section->addTextBreak();

        // ── Resolution Details ──
        $this->addDocxSectionTitle($section, 'Resolution Details');
        $this->addDocxInfoRow($section, 'Title', $resolution->title);
        $this->addDocxInfoRow($section, 'Status', ucfirst(str_replace('_', ' ', $resolution->status)));
        $this->addDocxInfoRow($section, 'Created By', $resolution->creator?->name ?? 'N/A');
        $this->addDocxInfoRow($section, 'Created Date', $resolution->created_at?->format('M d, Y h:i A') ?? 'N/A');

        if ($resolution->focal_person_name) {
            $this->addDocxInfoRow($section, 'Focal Person', $resolution->focal_person_name);
        }

        if ($resolution->approval_deadline) {
            $this->addDocxInfoRow($section, 'Approval Deadline', $resolution->approval_deadline->format('M d, Y'));
        }

        // ── Resolution Text ──
        if ($resolution->description) {
            $section->addTextBreak();
            $this->addDocxSectionTitle($section, 'Resolution Text');
            foreach (explode("\n", $resolution->description) as $line) {
                if (trim($line) !== '') {
                    $section->addText(trim($line), ['size' => 11], ['spaceAfter' => 40]);
                }
            }
        }

        // ── Meeting Details ──
        if ($resolution->meeting) {
            $section->addTextBreak();
            $this->addDocxSectionTitle($section, 'Meeting Details');
            $this->addDocxInfoRow($section, 'Meeting Title', $resolution->meeting->title);
            $this->addDocxInfoRow($section, 'Meeting Date', $resolution->meeting->date?->format('M d, Y') ?? 'N/A');

            if ($resolution->meeting->location) {
                $this->addDocxInfoRow($section, 'Location', $resolution->meeting->location);
            }

            // Structured agenda from meeting
            $agendaItems = $resolution->meeting->structured_agenda;
            if (count($agendaItems) > 0) {
                $section->addTextBreak();
                $this->addDocxSectionTitle($section, 'Agenda');
                foreach ($agendaItems as $item) {
                    $section->addListItem(trim($item), 0, ['size' => 10], ['spaceAfter' => 20]);
                }
            } elseif ($resolution->meeting->agenda) {
                $this->addDocxInfoRow($section, 'Agenda', $resolution->meeting->agenda);
            }

            if ($resolution->meeting->minutes_summary) {
                $section->addTextBreak();
                $this->addDocxSectionTitle($section, 'Minutes Summary');
                $section->addText($resolution->meeting->minutes_summary, ['size' => 10]);
            }
        }

        // ── Budget Line Items (same as PDF) ──
        if ($resolution->lineItems->isNotEmpty()) {
            $section->addTextBreak();
            $this->addDocxSectionTitle($section, 'Budget Line Items');

            $table = $section->addTable([
                'borderSize' => 6, 'borderColor' => 'E5E7EB', 'cellMargin' => 80,
            ]);

            $headerStyle = ['bold' => true, 'size' => 9, 'color' => '374151'];
            $table->addRow();
            $table->addCell(2000)->addText('Category', $headerStyle);
            $table->addCell(2500)->addText('Description', $headerStyle);
            $table->addCell(900)->addText('Qty', $headerStyle);
            $table->addCell(900)->addText('Unit', $headerStyle);
            $table->addCell(1400)->addText('Unit Cost', $headerStyle);
            $table->addCell(1400)->addText('Total', $headerStyle);

            $cellStyle = ['size' => 9];
            foreach ($resolution->lineItems as $item) {
                $table->addRow();
                $table->addCell(2000)->addText($item->category, $cellStyle);
                $table->addCell(2500)->addText($item->description, $cellStyle);
                $table->addCell(900)->addText(number_format((float) $item->quantity, 2), $cellStyle);
                $table->addCell(900)->addText($item->unit, $cellStyle);
                $table->addCell(1400)->addText('₱' . number_format((float) $item->unit_cost, 2), $cellStyle);
                $table->addCell(1400)->addText('₱' . number_format((float) $item->total, 2), $cellStyle);
            }

            $table->addRow();
            $table->addCell(7700, ['gridSpan' => 5])->addText('Grand Total', ['bold' => true, 'size' => 9]);
            $table->addCell(1400)->addText(
                '₱' . number_format($resolution->grand_total, 2),
                ['bold' => true, 'size' => 9, 'color' => '0C6D57']
            );
        }

        // ── Approval Status ──
        $section->addTextBreak();
        $section->addText('Approval Status:', ['bold' => true, 'size' => 11, 'color' => '0C6D57']);
        $section->addText(
            'Resolution Date: ' . ($options['document_date'] ?? now()->format('F j, Y')) . '. ' .
            'This document was generated as an official resolution record.',
            ['size' => 10]
        );

        // ── Signature Blocks ──
        $section->addTextBreak(2);
        $sigTable = $section->addTable(['borderSize' => 0, 'cellMargin' => 80]);
        $sigTable->addRow();

        $sigCellStyle = ['size' => 10, 'bold' => true];
        $sigRoleStyle = ['size' => 9, 'color' => '6B7280'];

        $cell1 = $sigTable->addCell(3000, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $cell1->addTextBreak(3);
        $cell1->addText('_________________________________', ['size' => 10]);
        $cell1->addText($resolution->creator?->name ?? 'Secretary', $sigCellStyle);
        $cell1->addText('Prepared by', $sigRoleStyle);

        $cell2 = $sigTable->addCell(3000, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $cell2->addTextBreak(3);
        $cell2->addText('_________________________________', ['size' => 10]);
        $cell2->addText('Treasurer', $sigCellStyle);
        $cell2->addText('Checked by', $sigRoleStyle);

        $cell3 = $sigTable->addCell(3000, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $cell3->addTextBreak(3);
        $cell3->addText('_________________________________', ['size' => 10]);
        $cell3->addText('President', $sigCellStyle);
        $cell3->addText('Approved by', $sigRoleStyle);

        // ── Member Signature Table (matching PDF) ──
        $section->addTextBreak(2);
        $this->addDocxSectionTitle($section, 'Member Signatures');
        $section->addText(
            'This resolution requires 75% member approval. Members who approve must sign below.',
            ['size' => 9, 'color' => '6B7280'],
            ['spaceAfter' => 60]
        );

        $memberTable = $section->addTable([
            'borderSize' => 6, 'borderColor' => 'E5E7EB', 'cellMargin' => 60,
        ]);

        $rowStyle = ['bold' => true, 'size' => 8, 'color' => '374151'];
        $memberTable->addRow();
        $memberTable->addCell(400)->addText('#', $rowStyle);
        $memberTable->addCell(2800)->addText('Member Name', $rowStyle);
        $memberTable->addCell(2800)->addText('Signature', $rowStyle);
        $memberTable->addCell(1800)->addText('Date', $rowStyle);
        $memberTable->addCell(1800)->addText('Remarks', $rowStyle);

        $emptyCellStyle = ['size' => 9];
        for ($i = 1; $i <= 20; $i++) {
            $memberTable->addRow();
            $memberTable->addCell(400)->addText((string) $i, $emptyCellStyle);
            $memberTable->addCell(2800)->addText('', $emptyCellStyle);
            $memberTable->addCell(2800)->addText('', $emptyCellStyle);
            $memberTable->addCell(1800)->addText('', $emptyCellStyle);
            $memberTable->addCell(1800)->addText('', $emptyCellStyle);
        }

        // ── Footer ──
        $section->addTextBreak(2);
        $section->addText(
            'Generated by Pig-Sikap on ' . now()->format('M d, Y h:i A') . '. ' .
            'Resolution ' . ($resolution->resolution_number ?? 'Draft') . ' • ' .
            'Elite Visionaries of Humayingan SLP Association',
            ['size' => 8, 'color' => '6B7280'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );

        // ── Save to temp and store ──
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

        // Mirror PDF behavior: advance workflow_status and assign resolution number
        $updateData = [
            'generated_docx_path' => $filePath,
        ];

        // Only advance workflow if still at draft
        if (in_array($resolution->workflow_status, ['draft'])) {
            $updateData['workflow_status'] = 'generated';
            $updateData['version'] = $versionNumber;
        }

        // Assign resolution number if not yet assigned
        if (! $resolution->resolution_number) {
            $updateData['resolution_number'] = Resolution::generateResolutionNumber();
            $updateData['resolution_number_assigned_at'] = now();
        }

        $resolution->update($updateData);

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

    /**
     * Add a formatted section title to the DOCX.
     */
    private function addDocxSectionTitle($section, string $title): void
    {
        $section->addText($title, ['bold' => true, 'size' => 12, 'color' => '0C6D57'], ['spaceAfter' => 40]);
        $section->addText('', ['size' => 4]); // subtle spacer
    }

    /**
     * Add a key-value info row to the DOCX with bold label.
     */
    private function addDocxInfoRow($section, string $label, string $value): void
    {
        $section->addText(
            $label . ':  ' . $value,
            ['size' => 10, 'bold' => true],
            ['spaceAfter' => 20]
        );
        $run = $section->addTextRun(['spaceAfter' => 20]);
        $run->addText('', ['size' => 10]);
    }
}
