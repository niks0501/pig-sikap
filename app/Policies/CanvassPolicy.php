<?php

namespace App\Policies;

use App\Models\Canvass;
use App\Models\User;

class CanvassPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role?->slug, ['president', 'secretary', 'treasurer', 'system_admin']);
    }

    public function view(User $user, Canvass $canvass): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Canvass $canvass): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Canvass $canvass): bool
    {
        return $this->viewAny($user);
    }
}