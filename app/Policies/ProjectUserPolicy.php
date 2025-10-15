<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProjectUser;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ProjectUserPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProjectUser');
    }

    public function view(AuthUser $authUser, ProjectUser $projectUser): bool
    {
        return $authUser->can('View:ProjectUser');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProjectUser');
    }

    public function update(AuthUser $authUser, ProjectUser $projectUser): bool
    {
        return $authUser->can('Update:ProjectUser');
    }

    public function delete(AuthUser $authUser, ProjectUser $projectUser): bool
    {
        return $authUser->can('Delete:ProjectUser');
    }

    public function restore(AuthUser $authUser, ProjectUser $projectUser): bool
    {
        return $authUser->can('Restore:ProjectUser');
    }

    public function forceDelete(AuthUser $authUser, ProjectUser $projectUser): bool
    {
        return $authUser->can('ForceDelete:ProjectUser');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProjectUser');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProjectUser');
    }

    public function replicate(AuthUser $authUser, ProjectUser $projectUser): bool
    {
        return $authUser->can('Replicate:ProjectUser');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProjectUser');
    }
}
