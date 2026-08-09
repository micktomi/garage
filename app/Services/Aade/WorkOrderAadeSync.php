<?php

namespace App\Services\Aade;

use App\Models\WorkOrder;
use Micktomi\GarageAadeBridge\Outbox\OutboxManager;

/**
 * Bridges real WorkOrder lifecycle transitions (checked in / checked out) to
 * the garage-aade-bridge outbox. Called from WorkOrder::booted() at the exact
 * points that already define "vehicle physically entered/left the shop" —
 * never from a generic model observer.
 */
final class WorkOrderAadeSync
{
    public function __construct(private readonly OutboxManager $outbox) {}

    public function handleCheckedIn(WorkOrder $workOrder): void
    {
        $this->outbox->enqueueSendClient(
            'work_order',
            $workOrder->id,
            (new WorkOrderGarageAdapter($workOrder))->toNewClientData(),
        );
    }
}
