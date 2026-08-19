<?php

namespace App\Listeners\Aade;

use App\Models\WorkOrder;
use App\Support\Aade\OutboxHealth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Micktomi\GarageAadeBridge\Enums\DclOperation;
use Micktomi\GarageAadeBridge\Enums\TransmissionOutcome;
use Micktomi\GarageAadeBridge\Events\DclEntrySent;
use Micktomi\GarageAadeBridge\Outbox\Models\OutboxEntry;

/**
 * Leaves a record when a SendClient finally reached ΑΑΔΕ long after the
 * vehicle actually arrived, with nothing in the outbox explaining the delay.
 *
 * This is the one case the declaration flow cannot cover: if the machine, the
 * scheduler or the queue was down, no attempt was ever made, so there is no
 * failure to look at and nothing that could have been declared in advance.
 * ΑΑΔΕ therefore stamped `creationDateTime` at transmission time — later than
 * the real arrival — and the entry carries no «Απώλεια διασύνδεσης» marker.
 *
 * Reporting only, by explicit decision: "it went out late" is not evidence
 * that the ΑΑΔΕ link was down, and §5 defines the flag by that cause alone.
 * Whether this particular entry needs a manual correction at ΑΑΔΕ is for the
 * business to judge, and this log is what lets them judge it — after the
 * fact, because aade:outbox-alerts can only ever show entries still waiting.
 *
 * MUST stay synchronous — see SyncDeferredWorkOrderCompletion for why
 * DclEntrySent listeners cannot be queued.
 */
final class WarnOnLateUnmarkedSendClient
{
    public function handle(DclEntrySent $event): void
    {
        $entry = $event->entry;

        if ($entry->operation !== DclOperation::SendClient) {
            return;
        }

        // Already filed as a declared connection loss: it carries the real
        // entry time, so there is nothing unmarked about it.
        if (($entry->payload['transmissionFailure'] ?? null) !== null) {
            return;
        }

        // A recorded failure means the delay is already visible — the entry
        // passed through ambiguous/failed and the alert command showed it.
        $hasRecordedFailure = $entry->transmissions()
            ->where('outcome', '!=', TransmissionOutcome::Success->value)
            ->exists();

        if ($hasRecordedFailure) {
            return;
        }

        $arrivedAt = $this->vehicleArrivedAt($entry);
        $lagMinutes = (int) $arrivedAt->diffInMinutes($event->transmission->created_at ?? now(), absolute: true);

        if ($lagMinutes <= OutboxHealth::DISPATCHER_STALL_MINUTES) {
            return;
        }

        Log::warning('ΑΑΔΕ SendClient was filed late with no recorded failure and no «Απώλεια διασύνδεσης» marker. ΑΑΔΕ stamped creationDateTime at transmission time, not at the vehicle\'s actual arrival; this entry may need a manual correction.', [
            'outbox_entry_id' => $entry->id,
            'local_entity_type' => $entry->local_entity_type,
            'local_entity_id' => $entry->local_entity_id,
            'vehicle_arrived_at' => $arrivedAt->toAtomString(),
            'lag_minutes' => $lagMinutes,
        ]);
    }

    private function vehicleArrivedAt(OutboxEntry $entry): Carbon
    {
        if ($entry->local_entity_type === 'work_order') {
            $checkedInAt = WorkOrder::find((int) $entry->local_entity_id)?->checked_in_at;

            if ($checkedInAt !== null) {
                return $checkedInAt;
            }
        }

        // Nothing else links this entry to a real-world arrival; when the
        // submission was queued is the closest honest stand-in.
        return $entry->created_at;
    }
}
