<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

/** Everyone records payments; nobody edits one; only the owner deletes one. */
class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasValidRole();
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->hasValidRole();
    }

    public function create(User $user): bool
    {
        return $user->hasValidRole();
    }

    public function update(User $user, Payment $payment): bool
    {
        return false;
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->isOwner();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Payment $payment): bool
    {
        return false;
    }

    public function forceDelete(User $user, Payment $payment): bool
    {
        return false;
    }
}
