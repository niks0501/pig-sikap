<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workflow\GenerateDocxRequest;
use App\Http\Requests\Workflow\GeneratePdfRequest;
use App\Models\Resolution;
use App\Services\Workflow\DocumentGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * Handles PDF and DOCX document generation for resolutions.
 * Keeps controller thin – all logic in DocumentGenerationService.
 */
class ResolutionDocumentController extends Controller
{
    use RecordsAuditTrail;

    public function __construct(
        private readonly DocumentGenerationService $docService
    ) {}

    /**
     * Generate a resolution PDF.
     */
    public function generatePdf(GeneratePdfRequest $request, Resolution $resolution): RedirectResponse|JsonResponse
    {
        try {
            $result = $this->docService->generatePdf($resolution, $request->validated());

            if ($request->expectsJson()) {
                $resolution->load('documentVersions.generatedBy:id,name');

                return response()->json([
                    'message' => 'Resolution PDF generated successfully.',
                    'file_path' => $result['file_path'],
                    'version' => $result['version'],
                    'documentVersions' => $resolution->documentVersions->map(fn ($dv) => [
                        'id' => $dv->id,
                        'version_number' => $dv->version_number,
                        'document_type' => $dv->document_type,
                        'file_url' => $dv->file_url,
                        'file_size' => $dv->file_size,
                        'formatted_file_size' => $dv->formatted_file_size,
                        'file_hash' => $dv->file_hash,
                        'generated_at' => $dv->generated_at?->format('M d, Y h:i A'),
                        'generated_by' => $dv->generatedBy?->name,
                        'description' => $dv->description,
                    ]),
                ]);
            }

            return redirect()
                ->route('workflow.resolutions.show', $resolution)
                ->with('status', 'Resolution PDF generated successfully.');
        } catch (\Throwable $e) {
            Log::error('Resolution PDF generation failed', [
                'resolution_id' => $resolution->id,
                'user_id' => auth()->id(),
                'exception' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Failed to generate PDF. Please try again.'], 500);
            }

            return redirect()->back()->with('error', 'Failed to generate PDF. Please try again.');
        }
    }

    /**
     * Generate a resolution DOCX (editable draft).
     */
    public function generateDocx(GenerateDocxRequest $request, Resolution $resolution): RedirectResponse|JsonResponse
    {
        try {
            $result = $this->docService->generateDocx($resolution, $request->validated());

            if ($request->expectsJson()) {
                $resolution->load('documentVersions.generatedBy:id,name');

                return response()->json([
                    'message' => 'Editable DOCX draft created successfully.',
                    'file_path' => $result['file_path'],
                    'version' => $result['version'],
                    'documentVersions' => $resolution->documentVersions->map(fn ($dv) => [
                        'id' => $dv->id,
                        'version_number' => $dv->version_number,
                        'document_type' => $dv->document_type,
                        'file_url' => $dv->file_url,
                        'file_size' => $dv->file_size,
                        'formatted_file_size' => $dv->formatted_file_size,
                        'file_hash' => $dv->file_hash,
                        'generated_at' => $dv->generated_at?->format('M d, Y h:i A'),
                        'generated_by' => $dv->generatedBy?->name,
                        'description' => $dv->description,
                    ]),
                ]);
            }

            return redirect()
                ->route('workflow.resolutions.show', $resolution)
                ->with('status', 'Editable DOCX draft created successfully.');
        } catch (\Throwable $e) {
            Log::error('Resolution DOCX generation failed', [
                'resolution_id' => $resolution->id,
                'user_id' => auth()->id(),
                'exception' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Failed to generate DOCX. Please try again.'], 500);
            }

            return redirect()->back()->with('error', 'Failed to generate DOCX. Please try again.');
        }
    }
}
