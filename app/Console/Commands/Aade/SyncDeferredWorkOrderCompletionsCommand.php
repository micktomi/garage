<?php

namespace App\Console\Commands\Aade;

use App\Models\WorkOrder;
use App\Services\Aade\WorkOrderAadeSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Micktomi\GarageAadeBridge\Enums\DclOperation;
use Micktomi\GarageAadeBridge\Outbox\Models\OutboxEntry;
use Micktomi\GarageAadeBridge\Outbox\OutboxManager;
use Throwable;

/**
 * Safety net for App\Listeners\Aade\SyncDeferredWorkOrderCompletion: retries
 * handleCompleted() for every WorkOrder whose completion never made it into
 * the outbox as an update_client/cancel_client entry, whether because the
 * eager attempt in WorkOrder::booted() ran before dclId was resolved, or
 * because the listener itself failed (see its own try/catch).
 *
 * Also the only place that surfaces "this has been stuck a while" — a
 * WorkOrder repeatedly showing up here past the warning threshold means the
 * listener/processing pipeline isn't keeping up, not just normal ΑΑΔΕ
 * latency.
 */
final class SyncDeferredWorkOrderCompletionsCommand extends Command
{
    protected $signature = 'aade:sync-deferred-work-order-completions';

    protected $description = 'Retry ΑΑΔΕ UpdateClient/CancelClient sync for Completed work orders still missing it, and warn about long-stuck ones.';

    private const WARNING_THRESHOLD_SECONDS = 600;

    public function handle(WorkOrderAadeSync $sync, OutboxManager $outbox): int
    {
        foreach ($sync->pendingCompletionSyncs() as $workOrder) {
            $sendClientEntry = OutboxEntry::query()
                ->where('local_entity_type', 'work_order')
                ->where('local_entity_id', (string) $workOrder->id)
                ->where('operation', DclOperation::SendClient->value)
                ->orderByDesc('id')
                ->first();

            // No SendClient entry at all: this WorkOrder never entered the
            // ΑΑΔΕ flow (seeded/imported as already closed) — a permanent,
            // expected no-op, not a stuck pipeline.
            if ($sendClientEntry === null) {
                continue;
            }

            // Only actually attempt handleCompleted() once dclId is
            // resolved: it no-ops (and itself logs its own warning) every
            // time it's called with no dclId, so calling it unconditionally
            // on every sweep tick would spam that log every run for any
            // still-unresolved WorkOrder, regardless of our own threshold
            // below. Cases where dclId is still null (still pending, or
            // permanently/ambiguously failed) fall straight through to the
            // staleness check instead.
            if ($outbox->resolveDclId('work_order', $workOrder->id) !== null) {
                try {
                    $sync->handleCompleted($workOrder);
                } catch (Throwable $exception) {
                    // Same reasoning as the DclEntrySent listener: SendClient
                    // already succeeded, so one broken WorkOrder must not
                    // stop the sweep from covering the rest.
                    Log::error('Deferred ΑΑΔΕ completion sync failed inside the sweep command.', [
                        'work_order_id' => $workOrder->id,
                        'send_client_outbox_entry_id' => $sendClientEntry->id,
                        'exception_class' => $exception::class,
                        'exception_message' => $exception->getMessage(),
                    ]);
                }

                continue;
            }

            $this->warnIfStuck($workOrder, $sendClientEntry);
        }

        return self::SUCCESS;
    }

    private function warnIfStuck(WorkOrder $workOrder, OutboxEntry $sendClientEntry): void
    {
        $waitingSince = $workOrder->checked_out_at ?? $sendClientEntry->created_at;
        $waitingSeconds = now()->diffInSeconds($waitingSince, absolute: true);

        if ($waitingSeconds <= self::WARNING_THRESHOLD_SECONDS) {
            return;
        }

        Log::warning('ΑΑΔΕ deferred completion sync waiting longer than expected.', [
            'work_order_id' => $workOrder->id,
            'waiting_seconds' => $waitingSeconds,
            'deferred_state' => $this->describeDeferredState($sendClientEntry),
            'send_client_outbox_entry_id' => $sendClientEntry->id,
            'send_client_status' => $sendClientEntry->status->value,
            'send_client_failure_kind' => $sendClientEntry->failure_kind?->value,
        ]);
    }

    private function describeDeferredState(OutboxEntry $sendClientEntry): string
    {
        return $sendClientEntry->failure_kind !== null
            ? 'send_client_'.$sendClientEntry->failure_kind->value
            : 'send_client_'.$sendClientEntry->status->value;
    }
}
