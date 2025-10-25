<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ActOfWorkDetail;
use Illuminate\Auth\Access\HandlesAuthorization;

class ActOfWorkDetailPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ActOfWorkDetail');
    }

    public function view(AuthUser $authUser, ActOfWorkDetail $actOfWorkDetail): bool
    {
        return $authUser->can('View:ActOfWorkDetail');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ActOfWorkDetail');
    }

    public function update(AuthUser $authUser, ActOfWorkDetail $actOfWorkDetail): bool
    {
        return $authUser->can('Update:ActOfWorkDetail');
    }

    public function delete(AuthUser $authUser, ActOfWorkDetail $actOfWorkDetail): bool
    {
        return $authUser->can('Delete:ActOfWorkDetail');
    }

    public function restore(AuthUser $authUser, ActOfWorkDetail $actOfWorkDetail): bool
    {
        return $authUser->can('Restore:ActOfWorkDetail');
    }

    public function forceDelete(AuthUser $authUser, ActOfWorkDetail $actOfWorkDetail): bool
    {
        return $authUser->can('ForceDelete:ActOfWorkDetail');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ActOfWorkDetail');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ActOfWorkDetail');
    }

    public function replicate(AuthUser $authUser, ActOfWorkDetail $actOfWorkDetail): bool
    {
        return $authUser->can('Replicate:ActOfWorkDetail');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ActOfWorkDetail');
    }

}