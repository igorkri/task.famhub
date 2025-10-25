<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\ActOfWork;
use Illuminate\Auth\Access\HandlesAuthorization;

class ActOfWorkPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:ActOfWork');
    }

    public function view(User $user, ActOfWork $actOfWork): bool
    {
        return $user->can('View:ActOfWork');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:ActOfWork');
    }

    public function update(User $user, ActOfWork $actOfWork): bool
    {
        return $user->can('Update:ActOfWork');
    }

    public function delete(User $user, ActOfWork $actOfWork): bool
    {
        return $user->can('Delete:ActOfWork');
    }

    public function restore(User $user, ActOfWork $actOfWork): bool
    {
        return $user->can('Restore:ActOfWork');
    }

    public function forceDelete(User $user, ActOfWork $actOfWork): bool
    {
        return $user->can('ForceDelete:ActOfWork');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:ActOfWork');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:ActOfWork');
    }

    public function replicate(User $user, ActOfWork $actOfWork): bool
    {
        return $user->can('Replicate:ActOfWork');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:ActOfWork');
    }

}