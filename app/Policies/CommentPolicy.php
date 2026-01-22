<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Comment $comment): bool
    {
        return $user->hasRole('Product Manager')
            || $comment->task->project->members->contains($user->id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Product Manager', 'Developer']);
    }

    public function update(User $user, Comment $comment): bool
    {
        return $user->hasRole('Product Manager')
            || $comment->user_id === $user->id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $user->hasRole('Product Manager')
            || $comment->user_id === $user->id;
    }

    public function restore(User $user, Comment $comment): bool
    {
        return $user->hasRole('Product Manager');
    }

    public function forceDelete(User $user, Comment $comment): bool
    {
        return $user->hasRole('Product Manager');
    }
}
