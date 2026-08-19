<?php

namespace App\Services\Aade;

use App\Models\WorkOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Micktomi\GarageAadeBridge\Enums\DclOperation;
use Micktomi\GarageAadeBridge\Enums\OutboxStatus;
use Micktomi\GarageAadeBridge\Outbox\Models\OutboxEntry;
use Micktomi\GarageAadeBridge\Outbox\PayloadCodec;
use Micktomi\GarageAadeBridge\Support\PayloadChecksum;
use RuntimeException;

/**
 * Marks a stranded SendClient as an «Απώλεια διασύνδεσης» resubmission
 * (`transmissionFailure=1`), per DCL REST API v1.1 §5 and Α.1057/2025 άρθρο 5.
 *
 * Two things this deliberately does not do:
 *
 * 1. **It never decides.** The specification defines the flag by *cause* —
 *    the link was down — and gives no duration after which a late submission
 *    becomes an outage. Nothing in the outbox can distinguish "the garage's
 *    line was down" from "our own dispatcher wasn't running", so the
 *    declaration is made by a person and recorded as theirs.
 * 2. **It never guesses the time.** §5 requires the real entry time in UTC
 *    alongside the flag; that is `work_orders.checked_in_at`. Without it the
 *    declaration is refused rather than filed with an invented timestamp —
 *    a wrong arrival time is a worse filing than a late one.
 *
 * Only `ambiguous` SendClients are eligible. Those are the entries where the
 * HTTP call never produced an answer, which is exactly the connection-loss
 * shape; a `failed` entry was answered and rejected, and a rejection is not
 * an outage.
 */
final class OutageResubmission
{
    /**
     * Requeue an ambiguous SendClient, declaring that the ΑΑΔΕ link was down
     * when the original attempt was made.
     *
     * @throws RuntimeException when the entry is not eligible, or when the
     *                          real vehicle entry time cannot be established
     */
    public function declareOutage(OutboxEntry $entry): void
    {
        $this->assertEligible($entry);

        $checkedInAt = $this->realEntryTime($entry);

        // Mutating the two keys in place rather than rebuilding NewClientData
        // field by field: the stored payload already is the encoded DTO, and
        // a field-by-field rebuild would silently drop anything the package
        // adds later. The decode below is the check that it stayed valid.
        $payload = $entry->payload;
        $payload['transmissionFailure'] = true;
        $payload['creationDateTime'] = $checkedInAt->toAtomString();

        PayloadCodec::decodeNewClient($payload);

        $entry->payload = $payload;
        $entry->payload_checksum = PayloadChecksum::forArray($payload);

        $this->requeue($entry);

        Log::warning('ΑΑΔΕ SendClient requeued as a declared connection-loss resubmission.', [
            'outbox_entry_id' => $entry->id,
            'local_entity_type' => $entry->local_entity_type,
            'local_entity_id' => $entry->local_entity_id,
            'transmission_failure' => 1,
            'creation_date_time' => $payload['creationDateTime'],
        ]);
    }

    /**
     * Requeue an ambiguous SendClient with the payload untouched — the
     * operator verified ΑΑΔΕ never received it, but the link was not the
     * reason, so nothing is declared.
     *
     * @throws RuntimeException when the entry is not eligible
     */
    public function requeueWithoutDeclaration(OutboxEntry $entry): void
    {
        $this->assertEligible($entry);

        $this->requeue($entry);

        Log::warning('ΑΑΔΕ SendClient requeued after manual verification, with no connection-loss declaration.', [
            'outbox_entry_id' => $entry->id,
            'local_entity_type' => $entry->local_entity_type,
            'local_entity_id' => $entry->local_entity_id,
        ]);
    }

    private function assertEligible(OutboxEntry $entry): void
    {
        if ($entry->operation !== DclOperation::SendClient) {
            throw new RuntimeException("Outbox entry #{$entry->id} is a {$entry->operation->value}; transmissionFailure exists only on SendClient (§5).");
        }

        if ($entry->status !== OutboxStatus::Ambiguous) {
            throw new RuntimeException("Outbox entry #{$entry->id} is {$entry->status->value}, not ambiguous. Only an unanswered SendClient can be a connection-loss resubmission.");
        }
    }

    private function realEntryTime(OutboxEntry $entry): Carbon
    {
        if ($entry->local_entity_type !== 'work_order') {
            throw new RuntimeException("Outbox entry #{$entry->id} is not linked to a work order, so the real vehicle entry time is unknown.");
        }

        $workOrder = WorkOrder::find((int) $entry->local_entity_id);

        return $workOrder?->checked_in_at ?? throw new RuntimeException(
            "Outbox entry #{$entry->id} has no recorded checked_in_at to declare as creationDateTime. §5 requires the real vehicle entry time; refusing to invent one."
        );
    }

    private function requeue(OutboxEntry $entry): void
    {
        // Same reset aade-dcl:retry-failed performs, so a declared entry
        // rejoins the ordinary send-pending flow with nothing special about
        // it beyond its payload.
        $entry->status = OutboxStatus::Pending;
        $entry->failure_kind = null;
        $entry->attempts = 0;
        $entry->next_retry_at = null;
        $entry->save();
    }
}
