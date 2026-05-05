<?php

namespace App\Services\Workflow;

use App\Models\Resolution;
use App\Models\ResolutionWithdrawalAuthorization;
use App\Models\User;

/**
 * Checks whether a resolution is eligible for withdrawal.
 * Enforces the 75% approval threshold, DSWD approval, and
 * authorized withdrawer rules.
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

    /**
     * Check if a user is authorized to withdraw for this resolution.
     *
     * @return array{authorized: bool, reason: ?string}
     */
    public function canUserWithdraw(Resolution $resolution, User $user): array
    {
        // Check if any authorizations exist for this resolution
        $authorizationsExist = ResolutionWithdrawalAuthorization::where('resolution_id', $resolution->id)
            ->whereNull('revoked_at')
            ->exists();

        // If no authorizations, fallback to existing behavior
        if (! $authorizationsExist) {
            return ['authorized' => true, 'reason' => null];
        }

        // Check if this user is in the authorized list (and not revoked)
        $authorization = ResolutionWithdrawalAuthorization::where('resolution_id', $resolution->id)
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->first();

        if (! $authorization) {
            return [
                'authorized' => false,
                'reason' => 'You are not authorized to withdraw for this resolution. Only designated members can withdraw.',
            ];
        }

        return ['authorized' => true, 'reason' => null];
    }
}