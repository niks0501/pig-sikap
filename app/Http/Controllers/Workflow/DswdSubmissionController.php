<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workflow\StoreDswdSubmissionRequest;
use App\Models\Resolution;
use App\Services\Workflow\DswdService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Manages DSWD submission status for resolutions.
 */
class DswdSubmissionController extends Controller
{
    use RecordsAuditTrail;

    public function __construct(
        private readonly DswdService $dswdService
    ) {}

    /**
     * Update DSWD submission status.
     */
    public function store(StoreDswdSubmissionRequest $request, Resolution $resolution): RedirectResponse|JsonResponse
    {
        $submission = $this->dswdService->submit(
            $resolution,
            $request->validated(),
            $request->user()
        );

        $this->recordAudit(
            $request,
            'dswd_status_updated',
            "DSWD status updated to '{$submission->status}' for resolution #{$resolution->id}",
            'workflow',
            [
                'resolution_id' => $resolution->id,
                'dswd_status' => $submission->status,
            ]
        );

        if ($request->expectsJson()) {
            $resolution->load(['approvals.user.role', 'dswdSubmission', 'withdrawals.requester', 'withdrawals.liquidationReport']);

            return response()->json([
                'message' => 'DSWD status updated successfully.',
                'submission' => $submission,
                'resolution' => [
                    'status' => $resolution->status,
                    'approval_percentage' => (float) $resolution->approval_percentage,
                    'approved_count' => $resolution->approved_count,
                    'has_met_threshold' => $resolution->hasMetApprovalThreshold(),
                    'dswdSubmission' => $resolution->dswdSubmission ? [
                        'id' => $resolution->dswdSubmission->id,
                        'status' => $resolution->dswdSubmission->status,
                        'submitted_at' => $resolution->dswdSubmission->submitted_at?->format('M d, Y'),
                        'submission_file_url' => $resolution->dswdSubmission->submissionFileUrl(),
                        'notes' => $resolution->dswdSubmission->notes,
                    ] : null,
                ],
            ]);
        }

        return redirect()
            ->route('workflow.resolutions.show', $resolution)
            ->with('status', 'DSWD submission status updated.');
    }
}
