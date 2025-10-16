<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TaskComment;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskCommentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TaskComment');
    }

    public function view(AuthUser $authUser, TaskComment $taskComment): bool
    {
        return $authUser->can('View:TaskComment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TaskComment');
    }

    public function update(AuthUser $authUser, TaskComment $taskComment): bool
    {
        return $authUser->can('Update:TaskComment');
    }

    public function delete(AuthUser $authUser, TaskComment $taskComment): bool
    {
        return $authUser->can('Delete:TaskComment');
    }

    public function restore(AuthUser $authUser, TaskComment $taskComment): bool
    {
        return $authUser->can('Restore:TaskComment');
    }

    public function forceDelete(AuthUser $authUser, TaskComment $taskComment): bool
    {
        return $authUser->can('ForceDelete:TaskComment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TaskComment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TaskComment');
    }

    public function replicate(AuthUser $authUser, TaskComment $taskComment): bool
    {
        return $authUser->can('Replicate:TaskComment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TaskComment');
    }

}