<?php

namespace App\Policies;

use App\Models\User;

class AssociationPolicySettingPolicy
{
    public function manage(User $user): bool
    {
        return in_array($user->role?->slug, ['president', 'system_admin']);
    }

    public function view(User $user): bool
    {
        return in_array($user->role?->slug, ['president', 'secretary', 'treasurer', 'system_admin']);
    }
}