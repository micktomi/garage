<?php

namespace App\Policies;

use App\Enums\WorkOrderStatus;
use App\Models\User;
use App\Models\WorkOrder;

class WorkOrderPolicy extends OwnerOnlyDestructivePolicy
{
    /**
     * Changing anything on an order that is already Completed means changing
     * what ΑΑΔΕ was told — a correction re-sends UpdateClient with a new
     * closure document, which is a tax decision, not a counter task.
     *
     * Keyed on the *stored* status, never on the submitted one: completing an
     * open order is ordinary daily work and stays open to everyone.
     */
    public function amendCompleted(User $user, WorkOrder $workOrder): bool
    {
        if ($workOrder->status !== WorkOrderStatus::Completed) {
            return true;
        }

        return $user->isOwner();
    }
}
