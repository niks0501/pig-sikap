<?php

namespace App\Policies;

use App\Models\DocumentUpload;
use App\Models\User;

class DocumentUploadPolicy
{
    public function view(User $user, DocumentUpload $upload): bool
    {
        return $user->is_active;
    }

    public function upload(User $user): bool
    {
        return $user->is_active;
    }

    public function review(User $user): bool
    {
        if (!$user->is_active) {
            return false;
        }

        $allowedRoles = ['president', 'secretary', 'treasurer', 'system_admin'];

        return in_array($user->role?->slug, $allowedRoles);
    }

    public function updateStatus(User $user, DocumentUpload $upload): bool
    {
        return $this->review($user);
    }

    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }
}