<?php

namespace App\Policies;

use App\Models\DocumentType;
use App\Models\User;

class DocumentTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, DocumentType $documentType): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->is_active && in_array($user->role?->slug, ['president', 'system_admin']);
    }

    public function update(User $user, DocumentType $documentType): bool
    {
        return $user->is_active && in_array($user->role?->slug, ['president', 'system_admin']);
    }

    public function delete(User $user, DocumentType $documentType): bool
    {
        return $user->is_active && in_array($user->role?->slug, ['president', 'system_admin']);
    }
}