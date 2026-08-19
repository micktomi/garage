<?php

namespace App\Services\Aade;

use App\Enums\WorkOrderStatus;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Micktomi\GarageAadeBridge\Enums\DclOperation;
use Micktomi\GarageAadeBridge\Outbox\Models\OutboxEntry;
use Micktomi\GarageAadeBridge\Outbox\OutboxManager;
use Micktomi\GarageAadeBridge\Outbox\PayloadCodec;
use Micktomi\GarageAadeBridge\Support\PayloadChecksum;
use Throwable;

/**
 * Bridges real WorkOrder lifecycle transitions (checked in / checked out) to
 * the garage-aade-bridge outbox. Called from WorkOrder::booted() at the exact
 * points that already define "vehicle physically entered/left the shop" —
 * never from a generic model observer.
 */
final class WorkOrderAadeSync
{
    /**
     * How far back the sweep looks for closure corrections that never reached
     * the outbox. A correction still unsynced after this long is not something
     * a retry loop can fix on its own.
     */
    private const CORRECTION_WINDOW_DAYS = 7;

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
     * A work order that opened an ΑΑΔΕ entry and then left the garage's books
     * — cancelled or deleted — has to close it: SendClient opens the entry and
     * only UpdateClient or CancelClient closes it, so without this the vehicle
     * stays registered as "in the shop" at ΑΑΔΕ indefinitely.
     *
     * Idempotent by construction: the CancelClient payload is derived purely
     * from the resolved dclId, so a second call produces the same checksum and
     * OutboxManager::enqueue() returns the existing entry.
     */
    public function handleCancelled(WorkOrder $workOrder): void
    {
        if ($this->outbox->resolveDclId('work_order', $workOrder->id) !== null) {
            $this->outbox->enqueueCancelClient('work_order', $workOrder->id);

            return;
        }

        // No SendClient entry at all means this work order never entered the
        // ΑΑΔΕ flow (seeded/imported, or created already closed) — an expected
        // no-op, same reasoning as SyncDeferredWorkOrderCompletionsCommand. A
        // SendClient that exists but never resolved a dclId is different:
        // there is an ΑΑΔΕ entry we may be unable to close.
        if ($this->hasSendClientEntry($workOrder)) {
            Log::warning('Cannot enqueue ΑΑΔΕ CancelClient: the SendClient for this work order has no resolved dclId.', [
                'work_order_id' => $workOrder->id,
            ]);
        }
    }

    private function hasSendClientEntry(WorkOrder $workOrder): bool
    {
        return OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->where('operation', DclOperation::SendClient->value)
            ->exists();
    }

    /**
     * True when the WorkOrder's *current* completion state has not been
     * enqueued yet — either because handleCompleted() never ran (or ran while
     * dclId was still unresolved and no-opped), or because closure_document /
     * non_issue_reason were corrected afterwards and that correction never
     * made it into the outbox.
     *
     * "Has this exact state been queued" is answered by the payload checksum,
     * the same value OutboxManager::enqueue() dedups on — so this can never
     * disagree with what an actual handleCompleted() call would do.
     */
    public function needsCompletionSync(WorkOrder $workOrder): bool
    {
        if ($workOrder->status !== WorkOrderStatus::Completed) {
            return false;
        }

        // A cancelled entry is closed at ΑΑΔΕ; nothing left to complete.
        if ($this->hasEntry($workOrder, DclOperation::CancelClient)) {
            return false;
        }

        $dclId = $this->outbox->resolveDclId('work_order', $workOrder->id);

        if ($dclId === null) {
            // No dclId means no payload can be built at all, so the only
            // answerable question is whether anything was ever queued.
            return ! $this->hasEntry($workOrder, DclOperation::UpdateClient);
        }

        $checksum = $this->completionChecksum($workOrder, $dclId);

        if ($checksum === null) {
            // The completion payload is unbuildable — e.g. Completed with no
            // closure_document, only reachable by bypassing Eloquent. Still
            // report it as pending: handleCompleted() will throw, and both
            // callers already catch and log that. Staying silent here would
            // hide a completed order ΑΑΔΕ can never be told about.
            return true;
        }

        return ! OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->where('operation', DclOperation::UpdateClient->value)
            ->where('payload_checksum', $checksum)
            ->exists();
    }

    /** @return Collection<int, WorkOrder> */
    public function pendingCompletionSyncs(): Collection
    {
        $synced = OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->whereIn('operation', [DclOperation::UpdateClient->value, DclOperation::CancelClient->value])
            ->pluck('local_entity_id')
            ->map(static fn (string $id): int => (int) $id);

        // Two cheap candidate sets, so the sweep never loads every completed
        // order ever recorded:
        //   1. never synced at all — the original safety net, unchanged, and
        //      the only set that keeps warning about long-stuck SendClients;
        //   2. edited recently — which may be a closure correction that never
        //      reached the outbox. Bounded by a window rather than by a
        //      timestamp comparison against the outbox, which would hinge on
        //      the database's one-second timestamp resolution.
        // needsCompletionSync() then decides on the actual payload.
        return WorkOrder::query()
            ->where('status', WorkOrderStatus::Completed)
            ->where(function ($query) use ($synced): void {
                $query->whereNotIn('id', $synced)
                    ->orWhere('updated_at', '>=', now()->subDays(self::CORRECTION_WINDOW_DAYS));
            })
            ->get()
            ->filter(fn (WorkOrder $workOrder): bool => $this->needsCompletionSync($workOrder))
            ->values();
    }

    private function hasEntry(WorkOrder $workOrder, DclOperation $operation): bool
    {
        return OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->where('operation', $operation->value)
            ->exists();
    }

    private function completionChecksum(WorkOrder $workOrder, int $dclId): ?string
    {
        try {
            return PayloadChecksum::forArray(PayloadCodec::encodeUpdateClient(
                (new WorkOrderGarageAdapter($workOrder))->toUpdateClientData($dclId),
            ));
        } catch (Throwable) {
            return null;
        }
    }
}
