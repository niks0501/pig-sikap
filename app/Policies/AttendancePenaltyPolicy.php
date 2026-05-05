<?php

namespace App\Policies;

use App\Models\AttendancePenalty;
use App\Models\User;

class AttendancePenaltyPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role?->slug, ['president', 'secretary', 'treasurer', 'system_admin']);
    }

    public function view(User $user, AttendancePenalty $penalty): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine if the user can waive a specific penalty (or any penalty if no instance is given).
     */
    public function waive(User $user, ?AttendancePenalty $penalty = null): bool
    {
        return in_array($user->role?->slug, ['president', 'system_admin']);
    }

    public function markPaid(User $user, AttendancePenalty $penalty = null): bool
    {
        return in_array($user->role?->slug, ['president', 'secretary', 'treasurer', 'system_admin']);
    }
}