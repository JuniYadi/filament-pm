<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Document $document): bool
    {
        // Standalone docs (no project): only creator and PM can view
        if (! $document->project_id) {
            return $user->hasRole('Product Manager')
                || $document->created_by === $user->id;
        }

        // Project docs: project members can view
        return $user->hasRole('Product Manager')
            || $document->project->members->contains($user->id)
            || $document->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Product Manager', 'Developer']);
    }

    public function update(User $user, Document $document): bool
    {
        return $user->hasRole('Product Manager')
            || $document->created_by === $user->id;
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->hasRole('Product Manager')
            || $document->created_by === $user->id;
    }

    public function restore(User $user, Document $document): bool
    {
        return $user->hasRole('Product Manager');
    }

    public function forceDelete(User $user, Document $document): bool
    {
        return $user->hasRole('Product Manager');
    }
}
