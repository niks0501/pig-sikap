<?php

namespace App\Policies;

use App\Models\Resolution;
use App\Models\User;

/**
 * Authorization policy for resolution document actions.
 * Each method checks the user's role AND the resolution's current workflow status.
 */
class ResolutionPolicy
{
    /**
     * Any active officer can view resolutions.
     */
    public function view(User $user, Resolution $resolution): bool
    {
        return $user->is_active;
    }

    /**
     * Secretary and President can create resolutions.
     */
    public function create(User $user): bool
    {
        return $user->is_active &&
            in_array($user->role->slug, ['secretary', 'president']);
    }

    /**
     * Secretary and President can generate documents (PDF/DOCX)
     * when the resolution is in draft or generated status.
     */
    public function generateDocuments(User $user, Resolution $resolution): bool
    {
        return $user->is_active &&
            in_array($user->role->slug, ['secretary', 'president']) &&
            in_array($resolution->workflow_status, ['draft', 'generated']);
    }

    /**
     * Secretary, Treasurer, and President can upload signed documents
     * when the resolution has been generated.
     */
    public function uploadSignedDocument(User $user, Resolution $resolution): bool
    {
        $allowedRoles = ['secretary', 'treasurer', 'president'];

        return $user->is_active &&
            in_array($user->role->slug, $allowedRoles) &&
            in_array($resolution->workflow_status, ['generated', 'printed']);
    }

    /**
     * Only President can verify the 75% approval threshold.
     */
    public function verifyApprovalThreshold(User $user, Resolution $resolution): bool
    {
        return $user->is_active &&
            in_array($user->role->slug, ['president']) &&
            in_array($resolution->workflow_status, ['signature_sheet_uploaded', 'pending_member_approval']);
    }

    /**
     * Secretary and President can submit to DSWD
     * after member approval is reached.
     */
    public function submitToDswd(User $user, Resolution $resolution): bool
    {
        return $user->is_active &&
            in_array($user->role->slug, ['secretary', 'president']) &&
            $resolution->workflow_status === 'member_approved';
    }

    /**
     * Secretary, Treasurer, and President can upload DSWD approval
     * when the resolution is pending DSWD approval.
     */
    public function uploadDswdApproval(User $user, Resolution $resolution): bool
    {
        $allowedRoles = ['secretary', 'treasurer', 'president'];

        return $user->is_active &&
            in_array($user->role->slug, $allowedRoles) &&
            $resolution->workflow_status === 'dswd_pending';
    }

    /**
     * Treasurer and President can create a withdrawal
     * after DSWD approval is received.
     */
    public function createWithdrawal(User $user, Resolution $resolution): bool
    {
        return $user->is_active &&
            in_array($user->role->slug, ['treasurer', 'president']) &&
            $resolution->workflow_status === 'dswd_approved';
    }

    /**
     * Can manage canvass records for this resolution.
     */
    public function manageCanvass(User $user, Resolution $resolution): bool
    {
        return $user->is_active &&
            in_array($user->role->slug, ['president', 'secretary', 'treasurer', 'canvasser']);
    }

    /**
     * Can designate withdrawers for this resolution (president only).
     */
    public function designateWithdrawers(User $user, Resolution $resolution): bool
    {
        return $user->is_active &&
            in_array($user->role->slug, ['president']);
    }

    /**
     * Can record approvals (must not be locked).
     */
    public function recordApproval(User $user, Resolution $resolution): bool
    {
        if ($resolution->is_approval_locked) {
            return false;
        }

        return $user->is_active &&
            in_array($user->role->slug, ['president', 'secretary']);
    }
}
