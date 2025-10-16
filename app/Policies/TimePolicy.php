<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Time;
use Illuminate\Auth\Access\HandlesAuthorization;

class TimePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Time');
    }

    public function view(AuthUser $authUser, Time $time): bool
    {
        return $authUser->can('View:Time');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Time');
    }

    public function update(AuthUser $authUser, Time $time): bool
    {
        return $authUser->can('Update:Time');
    }

    public function delete(AuthUser $authUser, Time $time): bool
    {
        return $authUser->can('Delete:Time');
    }

    public function restore(AuthUser $authUser, Time $time): bool
    {
        return $authUser->can('Restore:Time');
    }

    public function forceDelete(AuthUser $authUser, Time $time): bool
    {
        return $authUser->can('ForceDelete:Time');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Time');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Time');
    }

    public function replicate(AuthUser $authUser, Time $time): bool
    {
        return $authUser->can('Replicate:Time');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Time');
    }

}