<?php

namespace App\Services\Aade;

use App\Enums\WorkOrderStatus;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Micktomi\GarageAadeBridge\Enums\DclOperation;
use Micktomi\GarageAadeBridge\Outbox\Models\OutboxEntry;
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

    /**
     * Requires a resolved dclId from a prior successful SendClient — checked
     * here (not left to OutboxManager::enqueueUpdateClient's own internal
     * check) specifically so a missing dclId degrades to a logged warning
     * instead of an exception bubbling out of a model event and breaking the
     * completion UI.
     */
    public function handleCompleted(WorkOrder $workOrder): void
    {
        if ($this->outbox->resolveDclId('work_order', $workOrder->id) === null) {
            Log::warning('Skipping ΑΑΔΕ UpdateClient: no successful SendClient found for this work order.', [
                'work_order_id' => $workOrder->id,
            ]);

            return;
        }

        $this->outbox->enqueueUpdateClient(
            'work_order',
            $workOrder->id,
            new WorkOrderGarageAdapter($workOrder),
        );
    }

    /**
     * True when a WorkOrder is Completed but the outbox has no
     * update_client/cancel_client entry for it yet — i.e. handleCompleted()
     * either hasn't run since, or ran while dclId was still unresolved and
     * no-opped. Drives the deferred-completion listener and sweep command;
     * handleCompleted() itself stays a plain, idempotent attempt and never
     * needs to know about "pending" as a distinct state.
     */
    public function needsCompletionSync(WorkOrder $workOrder): bool
    {
        if ($workOrder->status !== WorkOrderStatus::Completed) {
            return false;
        }

        return ! OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->whereIn('operation', [DclOperation::UpdateClient->value, DclOperation::CancelClient->value])
            ->exists();
    }

    /** @return Collection<int, WorkOrder> */
    public function pendingCompletionSyncs(): Collection
    {
        $alreadySynced = OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->whereIn('operation', [DclOperation::UpdateClient->value, DclOperation::CancelClient->value])
            ->pluck('local_entity_id')
            ->map(static fn (string $id): int => (int) $id);

        return WorkOrder::query()
            ->where('status', WorkOrderStatus::Completed)
            ->whereNotIn('id', $alreadySynced)
            ->get();
    }
}
