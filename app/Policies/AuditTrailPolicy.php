<?php

namespace App\Policies;

use App\Models\User;

class AuditTrailPolicy
{
    /**
     * Only the president may view the audit trail logbook.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('president');
    }
}
