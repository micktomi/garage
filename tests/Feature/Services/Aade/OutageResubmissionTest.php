<?php

namespace Tests\Feature\Services\Aade;

use App\Enums\ClosureDocument;
use App\Enums\WorkOrderStatus;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Micktomi\GarageAadeBridge\Enums\TransmissionOutcome;
use Micktomi\GarageAadeBridge\Events\DclEntrySent;
use Micktomi\GarageAadeBridge\Outbox\Models\OutboxEntry;
use Micktomi\GarageAadeBridge\Outbox\Models\Transmission;
use Micktomi\GarageAadeBridge\Outbox\PayloadCodec;
use Micktomi\GarageAadeBridge\Support\PayloadChecksum;
use Micktomi\GarageAadeBridge\Xml\NewClientXmlSerializer;
use Micktomi\GarageAadeBridge\Xml\XsdValidator;
use Tests\TestCase;

/**
 * `transmissionFailure=1` means «Απώλεια διασύνδεσης» (spec §5, Α.1057/2025
 * άρθρο 5) — a cause, not a delay. The spec gives no duration that turns a
 * late submission into an outage, so nothing here infers one.
 *
 * The declaration is made by a human, on named entries, after they have
 * checked with RequestClients that ΑΑΔΕ never received the submission. That
 * check is not optional: an ambiguous SendClient may already exist ΑΑΔΕ-side,
 * and resending it opens a second Digital Client List entry for the same
 * vehicle.
 *
 * When declared, `creationDateTime` must accompany it — the *actual* vehicle
 * entry time, which is `work_orders.checked_in_at`, never "now".
 */
class OutageResubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_the_normal_online_path_sends_neither_field(): void
    {
        $payload = $this->sendClientEntryFor($this->workOrder('ΔΙΑ-1000'))->payload;

        // ΑΑΔΕ stamps creationDateTime itself unless transmissionFailure=1.
        $this->assertNull($payload['creationDateTime']);
        $this->assertNull($payload['transmissionFailure']);
    }

    public function test_a_declared_outage_stamps_the_flag_and_the_real_entry_time(): void
    {
        Carbon::setTestNow('2026-08-14 08:15:00');
        $workOrder = $this->workOrder('ΔΙΑ-2000');
        $entry = $this->strandedByOutage($workOrder);
        Carbon::setTestNow('2026-08-14 11:40:00');

        $this->artisan('aade:resend-ambiguous', [
            'id' => [$entry->id],
            '--connection-lost' => 'yes',
            '--verified-not-received' => true,
        ])->assertSuccessful();

        $entry = $entry->fresh();

        $this->assertTrue($entry->payload['transmissionFailure']);
        $this->assertSame(
            $workOrder->checked_in_at->toAtomString(),
            $entry->payload['creationDateTime'],
            'creationDateTime must be the real vehicle entry time, not the retransmission time.',
        );

        // Requeued for dispatch, and the outbox dedup key follows the payload.
        $this->assertSame('pending', $entry->status->value);
        $this->assertNull($entry->failure_kind);
        $this->assertSame(0, $entry->attempts);
        $this->assertTrue($entry->isDue());
        $this->assertSame(PayloadChecksum::forArray($entry->payload), $entry->payload_checksum);
    }

    public function test_the_declared_payload_is_a_valid_aade_document(): void
    {
        $workOrder = $this->workOrder('ΔΙΑ-3000');
        $entry = $this->strandedByOutage($workOrder);

        $this->artisan('aade:resend-ambiguous', [
            'id' => [$entry->id],
            '--connection-lost' => 'yes',
            '--verified-not-received' => true,
        ])->assertSuccessful();

        $document = (new NewClientXmlSerializer)->serialize(
            PayloadCodec::decodeNewClient($entry->fresh()->payload)
        );

        $this->assertSame([], (new XsdValidator)->validate($document, 'SendClient-v1.1.xsd'));

        $xml = $document->saveXML();
        // The XSD restricts transmissionFailure to the fixed value 1.
        $this->assertStringContainsString('<transmissionFailure>1</transmissionFailure>', $xml);
        // §5: "ο χρήστης στέλνει το πεδίο creationDateTime σε UTC μορφή".
        $this->assertMatchesRegularExpression('/<creationDateTime>\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z<\/creationDateTime>/', $xml);
    }

    public function test_answering_no_requeues_without_declaring_an_outage(): void
    {
        $entry = $this->strandedByOutage($this->workOrder('ΔΙΑ-4000'));
        $originalChecksum = $entry->payload_checksum;

        $this->artisan('aade:resend-ambiguous', [
            'id' => [$entry->id],
            '--connection-lost' => 'no',
            '--verified-not-received' => true,
        ])->assertSuccessful();

        $entry = $entry->fresh();

        $this->assertNull($entry->payload['transmissionFailure']);
        $this->assertNull($entry->payload['creationDateTime']);
        $this->assertSame($originalChecksum, $entry->payload_checksum);
        $this->assertSame('pending', $entry->status->value);
    }

    public function test_the_operator_must_answer_the_connection_question(): void
    {
        $entry = $this->strandedByOutage($this->workOrder('ΔΙΑ-5000'));

        $this->artisan('aade:resend-ambiguous', [
            'id' => [$entry->id],
            '--verified-not-received' => true,
            '--no-interaction' => true,
        ])->assertFailed();

        $this->assertSame('ambiguous', $entry->fresh()->status->value);
    }

    public function test_the_operator_must_confirm_aade_never_received_it(): void
    {
        $entry = $this->strandedByOutage($this->workOrder('ΔΙΑ-6000'));

        $this->artisan('aade:resend-ambiguous', [
            'id' => [$entry->id],
            '--connection-lost' => 'yes',
            '--no-interaction' => true,
        ])->assertFailed();

        $this->assertSame('ambiguous', $entry->fresh()->status->value);
        $this->assertNull($entry->fresh()->payload['transmissionFailure']);
    }

    public function test_the_operator_is_asked_both_questions_out_loud(): void
    {
        $workOrder = $this->workOrder('ΔΙΑ-6100');
        $entry = $this->strandedByOutage($workOrder);

        $this->artisan('aade:resend-ambiguous', ['id' => [$entry->id]])
            ->expectsConfirmation('Έχετε επιβεβαιώσει με RequestClients ότι η ΑΑΔΕ ΔΕΝ έχει αυτή την εγγραφή;', 'yes')
            ->expectsConfirmation('Είχε πέσει η διασύνδεση με την ΑΑΔΕ όταν έγινε η αρχική προσπάθεια;', 'yes')
            ->assertSuccessful();

        $this->assertTrue($entry->fresh()->payload['transmissionFailure']);
    }

    public function test_an_unverified_entry_is_never_resent_interactively_either(): void
    {
        $entry = $this->strandedByOutage($this->workOrder('ΔΙΑ-6200'));

        $this->artisan('aade:resend-ambiguous', ['id' => [$entry->id]])
            ->expectsConfirmation('Έχετε επιβεβαιώσει με RequestClients ότι η ΑΑΔΕ ΔΕΝ έχει αυτή την εγγραφή;', 'no')
            ->assertFailed();

        $this->assertSame('ambiguous', $entry->fresh()->status->value);
    }

    public function test_only_ambiguous_send_clients_can_be_declared(): void
    {
        $workOrder = $this->workOrder('ΔΙΑ-7000');
        $entry = $this->sendClientEntryFor($workOrder);
        $entry->update(['status' => 'failed', 'failure_kind' => 'permanent']);

        $this->artisan('aade:resend-ambiguous', [
            'id' => [$entry->id],
            '--connection-lost' => 'yes',
            '--verified-not-received' => true,
        ])->assertFailed();

        $this->assertSame('failed', $entry->fresh()->status->value);
        $this->assertNull($entry->fresh()->payload['transmissionFailure']);
    }

    public function test_an_entry_with_no_real_entry_time_is_refused_rather_than_invented(): void
    {
        $workOrder = $this->workOrder('ΔΙΑ-8000');
        $entry = $this->strandedByOutage($workOrder);

        // Raw update: checked_in_at is what the declaration has to state, and
        // guessing it would misreport the vehicle's arrival to ΑΑΔΕ.
        WorkOrder::query()->whereKey($workOrder->id)->update(['checked_in_at' => null]);

        $this->artisan('aade:resend-ambiguous', [
            'id' => [$entry->id],
            '--connection-lost' => 'yes',
            '--verified-not-received' => true,
        ])->assertFailed();

        $this->assertSame('ambiguous', $entry->fresh()->status->value);
    }

    public function test_the_packages_own_requeue_never_declares_an_outage(): void
    {
        $entry = $this->strandedByOutage($this->workOrder('ΔΙΑ-9000'));

        $this->artisan('aade-dcl:retry-failed', ['--include-ambiguous' => true])->assertSuccessful();

        $entry = $entry->fresh();

        $this->assertSame('pending', $entry->status->value);
        $this->assertNull(
            $entry->payload['transmissionFailure'],
            'The flag may only ever be set by an explicit human declaration.',
        );
    }

    public function test_completion_payloads_are_left_alone(): void
    {
        $workOrder = $this->workOrder('ΔΙΑ-9100');

        OutboxEntry::query()
            ->where('local_entity_id', (string) $workOrder->id)
            ->where('operation', 'send_client')
            ->update(['status' => 'sent', 'dcl_id' => 100000000830764]);

        $workOrder->update([
            'status' => WorkOrderStatus::Completed,
            'closure_document' => ClosureDocument::RetailReceipt,
        ]);

        $update = OutboxEntry::query()
            ->where('local_entity_id', (string) $workOrder->id)
            ->where('operation', 'update_client')
            ->sole();

        // §4.2.2: completionDateTime is "Συμπληρώνεται από την υπηρεσία",
        // unconditionally — there is no outage exception for it.
        $this->assertNull($update->payload['completionDateTime']);
    }

    public function test_a_send_client_nothing_ever_attempted_is_reported_but_not_flagged(): void
    {
        Carbon::setTestNow('2026-08-14 08:00:00');
        $entry = $this->sendClientEntryFor($this->workOrder('ΔΙΑ-9200'));
        Carbon::setTestNow('2026-08-14 14:00:00');

        $this->assertSame(0, $entry->transmissions()->count());

        $this->artisan('aade:outbox-alerts')
            ->expectsOutputToContain('#'.$entry->id.' send_client')
            ->assertFailed();

        // Reported so a human can decide — never auto-declared as an outage,
        // because "nothing tried" is not evidence of a lost connection.
        $this->assertNull($entry->fresh()->payload['transmissionFailure']);
    }

    public function test_a_send_client_that_finally_went_out_late_leaves_a_warning_behind(): void
    {
        Carbon::setTestNow('2026-08-14 08:00:00');
        $entry = $this->sendClientEntryFor($this->workOrder('ΔΙΑ-9300'));
        Carbon::setTestNow('2026-08-14 14:00:00');

        Log::spy();

        event(new DclEntrySent($entry, $this->successfulTransmission($entry)));

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context = []): bool => str_contains($message, 'ΑΑΔΕ')
                && ($context['outbox_entry_id'] ?? null) === $entry->id);
    }

    public function test_a_prompt_send_client_leaves_no_warning(): void
    {
        $entry = $this->sendClientEntryFor($this->workOrder('ΔΙΑ-9400'));

        Log::spy();

        event(new DclEntrySent($entry, $this->successfulTransmission($entry)));

        Log::shouldNotHaveReceived('warning');
    }

    private function successfulTransmission(OutboxEntry $entry): Transmission
    {
        return Transmission::create([
            'outbox_id' => $entry->id,
            'correlation_id' => $entry->correlation_id,
            'attempt_number' => 1,
            'endpoint' => 'SendClient',
            'http_method' => 'POST',
            'http_status' => 200,
            'outcome' => TransmissionOutcome::Success->value,
            'remote_identifier' => 100000000830764,
            'duration_ms' => 120,
        ]);
    }

    /** The state a real connection loss leaves a SendClient in. */
    private function strandedByOutage(WorkOrder $workOrder): OutboxEntry
    {
        $entry = $this->sendClientEntryFor($workOrder);

        Transmission::create([
            'outbox_id' => $entry->id,
            'correlation_id' => $entry->correlation_id,
            'attempt_number' => 1,
            'endpoint' => 'SendClient',
            'http_method' => 'POST',
            'http_status' => null,
            'outcome' => TransmissionOutcome::Ambiguous->value,
            'duration_ms' => 5000,
        ]);

        $entry->update([
            'status' => 'ambiguous',
            'failure_kind' => 'ambiguous',
            'attempts' => 1,
            'last_error' => 'cURL error 7: Failed to connect to mydataapidev.aade.gr',
        ]);

        return $entry->fresh();
    }

    private function sendClientEntryFor(WorkOrder $workOrder): OutboxEntry
    {
        return OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->where('operation', 'send_client')
            ->sole();
    }

    private function workOrder(string $plate): WorkOrder
    {
        $customer = Customer::create(['full_name' => 'Πελάτης '.$plate]);
        $vehicle = Vehicle::create(['customer_id' => $customer->id, 'plate_number' => $plate]);

        return WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Έλεγχος',
            'labor_cost' => 0,
            'parts_cost' => 0,
            'total_cost' => 0,
        ]);
    }
}
