<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * The whole authorization model for this app, in one place: everybody who is
 * logged in may read and write; only the owner may destroy.
 *
 * Written as a shared base rather than five near-identical policies because
 * five copies of the same rule is five places for them to drift apart. A
 * model that later needs its own rule overrides just that method.
 *
 * Note the consequence of registering a policy at all: Filament then routes
 * *every* ability through it, so viewAny/view/create/update have to be
 * granted explicitly here or the panel goes dark.
 */
abstract class OwnerOnlyDestructivePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Model $model): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Model $model): bool
    {
        return true;
    }

    /**
     * Deleting a customer, vehicle, work order or appointment takes fiscal
     * history with it — including the link this app uses to close the
     * matching ΑΑΔΕ entry.
     */
    public function delete(User $user, Model $model): bool
    {
        return $user->isOwner();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isOwner();
    }

    public function restore(User $user, Model $model): bool
    {
        return $user->isOwner();
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $user->isOwner();
    }
}
