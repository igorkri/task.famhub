<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Workspace;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkspacePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Workspace');
    }

    public function view(AuthUser $authUser, Workspace $workspace): bool
    {
        return $authUser->can('View:Workspace');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Workspace');
    }

    public function update(AuthUser $authUser, Workspace $workspace): bool
    {
        return $authUser->can('Update:Workspace');
    }

    public function delete(AuthUser $authUser, Workspace $workspace): bool
    {
        return $authUser->can('Delete:Workspace');
    }

    public function restore(AuthUser $authUser, Workspace $workspace): bool
    {
        return $authUser->can('Restore:Workspace');
    }

    public function forceDelete(AuthUser $authUser, Workspace $workspace): bool
    {
        return $authUser->can('ForceDelete:Workspace');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Workspace');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Workspace');
    }

    public function replicate(AuthUser $authUser, Workspace $workspace): bool
    {
        return $authUser->can('Replicate:Workspace');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Workspace');
    }

}