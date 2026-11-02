<?php

namespace App\Policies;

use App\Enums\WorkOrderStatus;
use App\Models\User;
use App\Models\WorkOrder;

class WorkOrderPolicy extends OwnerOnlyDestructivePolicy
{
    /**
     * Changing anything on an order that is already Completed rewrites a
     * closed record — a correction, not a counter task.
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

    /**
     * The state machine's single permission-shaped exception: reopening a
     * completed order is a correction — filed too early, wrongly closed —
     * which is an owner's call, like every other amendment of a filed
     * order. The target must be an open state; completed-to-cancelled is
     * never a correction, and cancelled stays terminal for everyone.
     */
    public function reopenCompleted(User $user, WorkOrder $workOrder, WorkOrderStatus $target): bool
    {
        return $user->isOwner()
            && $workOrder->status === WorkOrderStatus::Completed
            && $target->isOpen();
    }
}
