<?php

namespace App\Services\Workflow;

use App\Models\Resolution;
use App\Models\ResolutionApproval;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Records member approvals and auto-advances resolution status
 * when the 75% threshold is met.
 */
class ApprovalService
{
    /**
     * Record batch approvals for a resolution.
     *
     * @param  array<int, array<string, mixed>>  $approvals
     */
    public function recordBatch(Resolution $resolution, array $approvals): Resolution
    {
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
     */
    public function record(Resolution $resolution, User $member, bool $approved, ?string $reason = null): ResolutionApproval
    {
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
