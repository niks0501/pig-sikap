<?php

namespace App\Services\Workflow;

use App\Models\AuditTrail;
use App\Models\Resolution;
use Illuminate\Support\Facades\DB;

/**
 * Manages workflow state transitions for resolutions,
 * enforcing sequential steps and validation rules.
 * Enhanced with member snapshot creation and approval locking.
 */
class WorkflowTransitionService
{
    public function __construct(
        private readonly DocumentStorageService $storageService,
        private readonly MemberSnapshotService $snapshotService
    ) {}

    /**
     * Allowed transitions: from status => [allowed next statuses].
     */
    private const TRANSITIONS = [
        'draft' => ['generated'],
        'generated' => ['signature_sheet_uploaded'],
        'printed' => ['signature_sheet_uploaded'],
        'signature_sheet_uploaded' => ['pending_member_approval', 'member_approved'],
        'pending_member_approval' => ['member_approved'],
        'member_approved' => ['dswd_pending'],
        'dswd_pending' => ['dswd_approved'],
        'dswd_approved' => ['withdrawal_ready', 'withdrawn'],
        'withdrawal_ready' => ['withdrawn'],
        'withdrawn' => ['archived'],
    ];

    /**
     * Transition to "signature_sheet_uploaded" after uploading a signed document.
     *
     * @param  array<string, mixed>  $data
     */
    public function transitionToSigned(Resolution $resolution, array $data): void
    {
        $this->assertCurrentStatus($resolution, ['generated', 'printed']);

        DB::transaction(function () use ($resolution, $data) {
            $filePath = $this->storageService->storeSignedDocument(
                $data['signed_document'],
                $resolution,
                'signed_resolution',
                $data['description'] ?? null
            );

            $resolution->update([
                'signed_file_path' => $filePath,
                'workflow_status' => 'signature_sheet_uploaded',
            ]);

            $this->logAudit('resolution_signed_uploaded', $resolution, [
                'file_path' => $filePath,
            ]);
        });
    }

    /**
     * Upload a signature sheet (physical signatures scanned).
     *
     * @param  array<string, mixed>  $data
     */
    public function uploadSignatureSheet(Resolution $resolution, array $data): void
    {
        DB::transaction(function () use ($resolution, $data) {
            $filePath = $this->storageService->storeSignedDocument(
                $data['signature_sheet'],
                $resolution,
                'signature_sheet',
                $data['description'] ?? null
            );

            $resolution->update(['physical_signatures_pdf_path' => $filePath]);

            $this->logAudit('signature_sheet_uploaded', $resolution, [
                'file_path' => $filePath,
            ]);
        });
    }

    /**
     * Transition to pending_member_approval – takes a member snapshot.
     */
    public function transitionToPendingApproval(Resolution $resolution): void
    {
        $this->assertCurrentStatus($resolution, ['signature_sheet_uploaded']);

        DB::transaction(function () use ($resolution) {
            // Take immutable snapshot of active members
            $this->snapshotService->takeSnapshot($resolution);

            $resolution->update(['workflow_status' => 'pending_member_approval']);

            $this->logAudit('resolution_pending_member_approval', $resolution, [
                'snapshot_taken' => true,
            ]);
        });
    }

    /**
     * Verify that the 75% approval threshold has been met.
     *
     * @throws \RuntimeException
     */
    public function verifyApprovalThreshold(Resolution $resolution): bool
    {
        if (! $resolution->hasMetApprovalThreshold()) {
            throw new \RuntimeException(
                'The 75% approval threshold has not been met. ' .
                "Current approval is {$resolution->approval_percentage}%."
            );
        }

        $resolution->update([
            'signature_verified_at' => now(),
            'workflow_status' => 'member_approved',
        ]);

        $this->logAudit('resolution_approval_verified', $resolution, [
            'approval_percentage' => $resolution->approval_percentage,
            'approved_count' => $resolution->approved_count,
        ]);

        return true;
    }

    /**
     * Submit resolution to DSWD.
     */
    public function submitToDswd(Resolution $resolution): void
    {
        $this->assertCurrentStatus($resolution, ['member_approved']);

        $resolution->update(['workflow_status' => 'dswd_pending']);

        $this->logAudit('resolution_submitted_to_dswd', $resolution);
    }

    /**
     * Upload DSWD approval document and lock approvals.
     *
     * @param  array<string, mixed>  $data
     */
    public function uploadDswdApproval(Resolution $resolution, array $data): void
    {
        $this->assertCurrentStatus($resolution, ['dswd_pending']);

        DB::transaction(function () use ($resolution, $data) {
            $filePath = $this->storageService->storeSignedDocument(
                $data['dswd_approval_file'],
                $resolution,
                'dswd_approval',
                $data['approval_notes'] ?? null
            );

            // Lock approval changes when DSWD approves
            $resolution->update([
                'dswd_approval_file_path' => $filePath,
                'workflow_status' => 'dswd_approved',
                'is_approval_locked' => true,
            ]);

            $this->logAudit('dswd_approval_uploaded', $resolution, [
                'file_path' => $filePath,
                'dswd_reference' => $data['dswd_reference_number'] ?? null,
            ]);
        });
    }

    /**
     * Assert the resolution is in one of the expected statuses.
     *
     * @param  list<string>  $expectedStatuses
     *
     * @throws \RuntimeException
     */
    private function assertCurrentStatus(Resolution $resolution, array $expectedStatuses): void
    {
        if (! in_array($resolution->workflow_status, $expectedStatuses)) {
            $expected = implode(', ', $expectedStatuses);

            throw new \RuntimeException(
                "This action requires the resolution to be in one of these statuses: {$expected}. " .
                "Current status: {$resolution->workflow_status}."
            );
        }
    }

    /**
     * Log an audit trail entry for a workflow action.
     */
    private function logAudit(string $action, Resolution $resolution, array $extraContext = []): void
    {
        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'module' => 'workflow',
            'description' => ucwords(str_replace('_', ' ', $action)) . " for resolution #{$resolution->resolution_number}",
            'context_json' => array_merge([
                'resolution_id' => $resolution->id,
                'workflow_status' => $resolution->workflow_status,
            ], $extraContext),
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
        ]);
    }
}