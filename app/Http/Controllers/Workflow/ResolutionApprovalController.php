<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Models\Resolution;
use App\Services\Workflow\WorkflowTransitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles approval threshold verification for resolutions.
 */
class ResolutionApprovalController extends Controller
{
    use RecordsAuditTrail;

    public function __construct(
        private readonly WorkflowTransitionService $transitionService
    ) {}

    /**
     * Verify that the 75% approval threshold has been met
     * and transition the resolution to DSWD pending status.
     */
    public function verifyThreshold(Request $request, Resolution $resolution): RedirectResponse|JsonResponse
    {
        $this->authorize('verifyApprovalThreshold', $resolution);

        try {
            $this->transitionService->verifyApprovalThreshold($resolution);

            // Auto-transition to DSWD pending
            $this->transitionService->submitToDswd($resolution->fresh());

            $this->recordAudit(
                $request,
                'approval_threshold_verified',
                "Verified 75% approval threshold for resolution #{$resolution->resolution_number}",
                'workflow',
                [
                    'resolution_id' => $resolution->id,
                    'approval_percentage' => $resolution->approval_percentage,
                ]
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => '75% approval threshold verified. Resolution ready for DSWD submission.',
                    'workflow_status' => $resolution->fresh()->workflow_status,
                ]);
            }

            return redirect()
                ->route('workflow.resolutions.show', $resolution)
                ->with('status', '75% approval threshold verified. Resolution ready for DSWD submission.');
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
