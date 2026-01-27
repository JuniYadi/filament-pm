<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ProjectPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Project');
    }

    public function view(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('View:Project');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Project');
    }

    public function update(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('Update:Project');
    }

    public function delete(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('Delete:Project');
    }

    public function restore(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('Restore:Project');
    }

    public function forceDelete(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('ForceDelete:Project');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Project');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Project');
    }

    public function replicate(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('Replicate:Project');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Project');
    }

    /**
     * Determine if the user can add members to the project.
     * Only project owners can add members.
     */
    public function addMember(AuthUser $authUser, Project $project): bool
    {
        return $project->owner_id === $authUser->id;
    }

    /**
     * Determine if the user can remove members from the project.
     * Only project owners can remove members, but they cannot remove themselves.
     */
    public function removeMember(AuthUser $authUser, Project $project, User $member): bool
    {
        // Only owners can remove members
        if ($project->owner_id !== $authUser->id) {
            return false;
        }

        // Owners cannot remove themselves
        if ($member->id === $authUser->id) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the user can invite members to the project.
     * Only project owners can send invitations.
     */
    public function inviteMember(AuthUser $authUser, Project $project): bool
    {
        return $project->owner_id === $authUser->id;
    }

    /**
     * Determine if the user can change member roles.
     * Only project owners can change roles.
     */
    public function changeMemberRole(AuthUser $authUser, Project $project): bool
    {
        return $project->owner_id === $authUser->id;
    }
}
