<?php

namespace App\Policies;

use App\Models\User;

/**
 * The one policy that is entirely owner-only, deliberately not extending
 * OwnerOnlyDestructivePolicy: anybody who can create or edit users can
 * promote themselves to owner and undo every other restriction in the
 * app. Staff run the shop all day; they never see this resource at all
 * (viewAny false also hides its Filament navigation).
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isOwner();
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isOwner();
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isOwner();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isOwner();
    }

    public function restore(User $user, User $model): bool
    {
        return $user->isOwner();
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->isOwner();
    }
}
