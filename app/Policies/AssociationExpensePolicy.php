<?php

namespace App\Policies;

use App\Models\AssociationExpense;
use App\Models\User;

class AssociationExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role?->slug, ['president', 'secretary', 'treasurer', 'system_admin']);
    }

    public function view(User $user, AssociationExpense $expense): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, AssociationExpense $expense): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, AssociationExpense $expense): bool
    {
        return $this->viewAny($user);
    }
}
