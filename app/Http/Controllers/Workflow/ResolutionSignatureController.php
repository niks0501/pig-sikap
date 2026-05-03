<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workflow\UploadSignedDocumentRequest;
use App\Models\Resolution;
use App\Services\Workflow\DocumentStorageService;
use App\Services\Workflow\WorkflowTransitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles uploads of signed resolution documents and signature sheets.
 */
class ResolutionSignatureController extends Controller
{
    use RecordsAuditTrail;

    public function __construct(
        private readonly WorkflowTransitionService $transitionService,
        private readonly DocumentStorageService $storageService
    ) {}

    /**
     * Upload a signed resolution copy.
     */
    public function uploadSigned(UploadSignedDocumentRequest $request, Resolution $resolution): RedirectResponse|JsonResponse
    {
        try {
            $this->transitionService->transitionToSigned($resolution, $request->validated());

            // Also upload signature sheet if provided
            if ($request->hasFile('signature_sheet')) {
                $this->transitionService->uploadSignatureSheet($resolution, [
                    'signature_sheet' => $request->file('signature_sheet'),
                    'description' => 'Signature sheet uploaded with signed document',
                ]);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Signed document uploaded successfully.',
                    'workflow_status' => $resolution->fresh()->workflow_status,
                ]);
            }

            return redirect()
                ->route('workflow.resolutions.show', $resolution)
                ->with('status', 'Signed document uploaded successfully.');
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Upload a separate signature sheet.
     */
    public function uploadSignatureSheet(Request $request, Resolution $resolution): RedirectResponse|JsonResponse
    {
        $request->validate([
            'signature_sheet' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->transitionService->uploadSignatureSheet($resolution, $request->only('signature_sheet', 'description'));

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Signature sheet uploaded successfully.']);
            }

            return redirect()
                ->route('workflow.resolutions.show', $resolution)
                ->with('status', 'Signature sheet uploaded successfully.');
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
