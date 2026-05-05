<?php

namespace App\Services\Workflow;

use App\Models\Resolution;
use App\Models\ResolutionApproval;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Records member approvals and auto-advances resolution status
 * when the 75% threshold is met. Enforces approval locking.
 */
class ApprovalService
{
    /**
     * Assert that the resolution is not locked for approval changes.
     *
     * @throws ValidationException
     */
    private function assertNotLocked(Resolution $resolution): void
    {
        if ($resolution->is_approval_locked) {
            throw ValidationException::withMessages([
                'approval' => ['Approval changes are locked. This resolution has already been approved by DSWD or has withdrawals.'],
            ]);
        }
    }

    /**
     * Record batch approvals for a resolution.
     *
     * @param  array<int, array<string, mixed>>  $approvals
     *
     * @throws ValidationException
     */
    public function recordBatch(Resolution $resolution, array $approvals): Resolution
    {
        $this->assertNotLocked($resolution);

        DB::transaction(function () use ($resolution, $approvals) {
            foreach ($approvals as $approval) {
                ResolutionApproval::updateOrCreate(
                    [
                        'resolution_id' => $resolution->id,
                        'user_id' => $approval['user_id'],
                    ],
                    [
                        'is_approved' => $approval['is_approved'],
                        'approved_at' => $approval['is_approved'] ? now() : null,
                        'rejection_reason' => $approval['rejection_reason'] ?? null,
                    ]
                );
            }

            // Auto-advance to 'approved' if threshold is met
            $resolution->refresh();

            if ($resolution->hasMetApprovalThreshold() && $resolution->status === 'pending_approval') {
                $resolution->update(['status' => 'approved']);
                event(new \App\Events\Workflow\ResolutionApproved($resolution, $resolution->approval_percentage));
            }
        });

        return $resolution->fresh(['approvals.user']);
    }

    /**
     * Record a single member's approval.
     *
     * @throws ValidationException
     */
    public function record(Resolution $resolution, User $member, bool $approved, ?string $reason = null): ResolutionApproval
    {
        $this->assertNotLocked($resolution);

        $approval = ResolutionApproval::updateOrCreate(
            [
                'resolution_id' => $resolution->id,
                'user_id' => $member->id,
            ],
            [
                'is_approved' => $approved,
                'approved_at' => $approved ? now() : null,
                'rejection_reason' => $reason,
            ]
        );

        // Check threshold for auto-advance
        $resolution->refresh();

        if ($resolution->hasMetApprovalThreshold() && $resolution->status === 'pending_approval') {
            $resolution->update(['status' => 'approved']);
            event(new \App\Events\Workflow\ResolutionApproved($resolution, $resolution->approval_percentage));
        }

        return $approval;
    }
}