<?php

namespace App\Listeners\Aade;

use App\Models\WorkOrder;
use App\Services\Aade\WorkOrderAadeSync;
use Illuminate\Support\Facades\Log;
use Micktomi\GarageAadeBridge\Enums\DclOperation;
use Micktomi\GarageAadeBridge\Events\DclEntrySent;
use Throwable;

/**
 * Closes the race window between a SendClient succeeding and the eager
 * handleCompleted() attempt in WorkOrder::booted() having already run with
 * no dclId to use — see WorkOrderAadeSync::pendingCompletionSyncs() for the
 * scheduled sweep (SyncDeferredWorkOrderCompletionsCommand) that covers
 * whatever this listener still misses.
 *
 * MUST stay synchronous — do NOT implement ShouldQueue. DclEntrySent is
 * dispatched by OutboxDispatcher::recordSuccess() *inside* its DB
 * transaction, right after dcl_id is persisted but before that transaction
 * commits (see garage-aade-bridge README, "## Public API" > Events). A
 * default queued listener would be pushed to a worker that may run in a
 * separate process/connection before the commit happens, and would then see
 * no dcl_id at all. Do not "optimize" this to a queued listener later — if
 * queuing is ever needed, it must use explicit after-commit semantics
 * (e.g. ShouldQueueAfterCommit), never plain ShouldQueue.
 */
final class SyncDeferredWorkOrderCompletion
{
    public function __construct(private readonly WorkOrderAadeSync $sync) {}

    public function handle(DclEntrySent $event): void
    {
        $entry = $event->entry;

        if ($entry->operation !== DclOperation::SendClient || $entry->local_entity_type !== 'work_order') {
            return;
        }

        $workOrder = WorkOrder::find((int) $entry->local_entity_id);

        if ($workOrder === null || ! $this->sync->needsCompletionSync($workOrder)) {
            return;
        }

        try {
            $this->sync->handleCompleted($workOrder);
        } catch (Throwable $exception) {
            // The SendClient this listener is reacting to already succeeded
            // at ΑΑΔΕ — its dcl_id is real and persisted. Letting this
            // exception bubble would roll back that same still-open outer
            // transaction and lose it. Swallow, log, and let the scheduled
            // sweep retry later.
            Log::error('Deferred ΑΑΔΕ completion sync failed inside DclEntrySent listener.', [
                'work_order_id' => $workOrder->id,
                'send_client_outbox_entry_id' => $entry->id,
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);
        }
    }
}
