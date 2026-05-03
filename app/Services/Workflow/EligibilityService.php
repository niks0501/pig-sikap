<?php

namespace App\Services\Workflow;

use App\Models\Resolution;

/**
 * Checks whether a resolution is eligible for withdrawal.
 * Enforces both the 75% approval threshold and DSWD approval.
 */
class EligibilityService
{
    /**
     * Determine if a resolution can proceed to withdrawal.
     *
     * @return array{eligible: bool, reasons: list<string>}
     */
    public function canWithdraw(Resolution $resolution): array
    {
        $reasons = [];
        $threshold = Resolution::APPROVAL_THRESHOLD;

        // Check approval threshold
        if (! $resolution->hasMetApprovalThreshold()) {
            $reasons[] = "Approval is below the required {$threshold}% threshold ({$resolution->approval_percentage}% achieved).";
        }

        // Check DSWD approval
        $dswd = $resolution->dswdSubmission;

        if (! $dswd || $dswd->status !== 'approved') {
            $reasons[] = 'DSWD approval has not been received yet.';
        }

        // Check remaining balance
        if ($resolution->remaining_balance <= 0) {
            $reasons[] = 'No remaining balance available for withdrawal.';
        }

        // Check resolution status (must be at least dswd_submitted)
        if (! in_array($resolution->status, ['dswd_submitted', 'withdrawn'])) {
            $reasons[] = "Resolution is not yet ready for withdrawal (current status: {$resolution->status}).";
        }

        return [
            'eligible' => empty($reasons),
            'reasons' => $reasons,
        ];
    }
}
