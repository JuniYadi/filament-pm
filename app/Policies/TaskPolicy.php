<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        return $user->hasRole('Product Manager')
            || $task->project->members->contains($user->id)
            || $task->assigned_to === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Product Manager', 'Developer']);
    }

    public function update(User $user, Task $task): bool
    {
        return $user->hasRole('Product Manager')
            || $task->project->owner_id === $user->id
            || $task->assigned_to === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->hasRole('Product Manager')
            || $task->project->owner_id === $user->id;
    }

    public function restore(User $user, Task $task): bool
    {
        return $user->hasRole('Product Manager');
    }

    public function forceDelete(User $user, Task $task): bool
    {
        return $user->hasRole('Product Manager');
    }
}
