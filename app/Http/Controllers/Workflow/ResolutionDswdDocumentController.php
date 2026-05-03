<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workflow\UploadDswdApprovalRequest;
use App\Models\Resolution;
use App\Services\Workflow\WorkflowTransitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Handles DSWD approval document uploads for resolutions.
 */
class ResolutionDswdDocumentController extends Controller
{
    use RecordsAuditTrail;

    public function __construct(
        private readonly WorkflowTransitionService $transitionService
    ) {}

    /**
     * Upload a DSWD approval document.
     */
    public function uploadApproval(UploadDswdApprovalRequest $request, Resolution $resolution): RedirectResponse|JsonResponse
    {
        try {
            $this->transitionService->uploadDswdApproval($resolution, $request->validated());

            $this->recordAudit(
                $request,
                'dswd_approval_uploaded',
                "Uploaded DSWD approval for resolution #{$resolution->resolution_number}",
                'workflow',
                [
                    'resolution_id' => $resolution->id,
                    'dswd_reference' => $request->dswd_reference_number,
                ]
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'DSWD approval uploaded successfully. Resolution ready for withdrawal.',
                    'workflow_status' => $resolution->fresh()->workflow_status,
                ]);
            }

            return redirect()
                ->route('workflow.resolutions.show', $resolution)
                ->with('status', 'DSWD approval uploaded successfully. Resolution ready for withdrawal.');
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }
}
