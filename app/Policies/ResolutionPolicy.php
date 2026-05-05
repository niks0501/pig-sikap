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
     * Secretary, President, and Admin can create resolutions.
     */
    public function create(User $user): bool
    {
        return $user->is_active &&
            in_array($user->role->slug, ['secretary', 'president', 'system_admin']);
    }

    /**
     * Secretary, President, and Admin can generate documents (PDF/DOCX)
     * when the resolution is in draft or generated status.
     */
    public function generateDocuments(User $user, Resolution $resolution): bool
    {
        return $user->is_active &&
            in_array($user->role->slug, ['secretary', 'president', 'system_admin']) &&
            in_array($resolution->workflow_status, ['draft', 'generated']);
    }

    /**
     * Secretary, Treasurer, President, and Admin can upload signed documents
     * when the resolution has been generated.
     */
    public function uploadSignedDocument(User $user, Resolution $resolution): bool
    {
        $allowedRoles = ['secretary', 'treasurer', 'president', 'system_admin'];

        return $user->is_active &&
            in_array($user->role->slug, $allowedRoles) &&
            in_array($resolution->workflow_status, ['generated', 'printed']);
    }

    /**
     * Only President and Admin can verify the 75% approval threshold.
     */
    public function verifyApprovalThreshold(User $user, Resolution $resolution): bool
    {
        return $user->is_active &&
            in_array($user->role->slug, ['president', 'system_admin']) &&
            in_array($resolution->workflow_status, ['signature_sheet_uploaded', 'pending_member_approval']);
    }

    /**
     * Secretary, President, and Admin can submit to DSWD
     * after member approval is reached.
     */
    public function submitToDswd(User $user, Resolution $resolution): bool
    {
        return $user->is_active &&
            in_array($user->role->slug, ['secretary', 'president', 'system_admin']) &&
            $resolution->workflow_status === 'member_approved';
    }

    /**
     * Secretary, Treasurer, President, and Admin can upload DSWD approval
     * when the resolution is pending DSWD approval.
     */
    public function uploadDswdApproval(User $user, Resolution $resolution): bool
    {
        $allowedRoles = ['secretary', 'treasurer', 'president', 'system_admin'];

        return $user->is_active &&
            in_array($user->role->slug, $allowedRoles) &&
            $resolution->workflow_status === 'dswd_pending';
    }

    /**
     * Treasurer, President, and Admin can create a withdrawal
     * after DSWD approval is received.
     */
    public function createWithdrawal(User $user, Resolution $resolution): bool
    {
        return $user->is_active &&
            in_array($user->role->slug, ['treasurer', 'president', 'system_admin']) &&
            $resolution->workflow_status === 'dswd_approved';
    }

    /**
     * Can manage canvass records for this resolution.
     */
    public function manageCanvass(User $user, Resolution $resolution): bool
    {
        return $user->is_active &&
            in_array($user->role->slug, ['president', 'secretary', 'treasurer', 'system_admin']);
    }

    /**
     * Can designate withdrawers for this resolution (president/admin only).
     */
    public function designateWithdrawers(User $user, Resolution $resolution): bool
    {
        return $user->is_active &&
            in_array($user->role->slug, ['president', 'system_admin']);
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
            in_array($user->role->slug, ['president', 'secretary', 'system_admin']);
    }
}
