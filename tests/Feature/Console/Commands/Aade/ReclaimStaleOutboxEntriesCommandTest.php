<?php

namespace Tests\Feature\Console\Commands\Aade;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Micktomi\GarageAadeBridge\Outbox\Models\OutboxEntry;
use Micktomi\GarageAadeBridge\Support\CorrelationId;
use Tests\TestCase;

/**
 * OutboxDispatcher::claim() flips an entry to `processing` and only then makes
 * the HTTP call. If the process dies in between — deploy, reboot, OOM, power
 * cut — the row stays `processing` forever: aade-dcl:send-pending only looks
 * at `pending` and aade-dcl:retry-failed only at `failed`/`ambiguous`. The
 * customer is never registered with ΑΑΔΕ and nothing ever says so.
 */
class ReclaimStaleOutboxEntriesCommandTest extends TestCase
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

    public function test_an_entry_abandoned_mid_flight_is_invisible_to_every_other_command(): void
    {
        $entry = $this->abandonedEntry('send_client', minutesAgo: 30);

        $this->artisan('aade-dcl:send-pending')->assertSuccessful();
        $this->artisan('aade-dcl:retry-failed')->assertSuccessful();

        $this->assertSame(
            'processing',
            $entry->fresh()->status->value,
            'Neither existing command can recover an entry abandoned mid-flight.',
        );
    }

    public function test_an_abandoned_send_client_is_reclaimed_as_ambiguous(): void
    {
        $entry = $this->abandonedEntry('send_client', minutesAgo: 30);

        $this->artisan('aade:reclaim-stale-outbox')->assertSuccessful();

        $entry = $entry->fresh();

        $this->assertSame('ambiguous', $entry->status->value);
        $this->assertSame('ambiguous', $entry->failure_kind->value);
        $this->assertNotNull($entry->last_error);
    }

    public function test_a_reclaimed_send_client_is_never_blindly_resent(): void
    {
        $entry = $this->abandonedEntry('send_client', minutesAgo: 30);

        $this->artisan('aade:reclaim-stale-outbox')->assertSuccessful();
        $this->artisan('aade-dcl:send-pending')->assertSuccessful();

        // ΑΑΔΕ has no idempotency key for SendClient: the submission may well
        // have landed before the crash, so resending it would open a second
        // Digital Client List entry for the same vehicle.
        $this->assertSame('ambiguous', $entry->fresh()->status->value);
        $this->assertSame(0, $entry->fresh()->transmissions()->count());
    }

    public function test_an_abandoned_update_client_is_reclaimed_as_pending(): void
    {
        $entry = $this->abandonedEntry('update_client', minutesAgo: 30);

        $this->artisan('aade:reclaim-stale-outbox')->assertSuccessful();

        $entry = $entry->fresh();

        // UpdateClient carries initialDclId, so replaying it is idempotent at
        // ΑΑΔΕ — this one is safe to hand straight back to send-pending.
        $this->assertSame('pending', $entry->status->value);
        $this->assertNull($entry->failure_kind);
        $this->assertNull($entry->next_retry_at);
        $this->assertTrue($entry->isDue());
    }

    public function test_an_entry_a_live_worker_is_still_processing_is_left_alone(): void
    {
        $entry = $this->abandonedEntry('send_client', minutesAgo: 2);

        $this->artisan('aade:reclaim-stale-outbox')->assertSuccessful();

        $this->assertSame('processing', $entry->fresh()->status->value);
    }

    public function test_entries_in_any_other_state_are_never_touched(): void
    {
        Carbon::setTestNow('2026-08-15 10:00:00');

        $pending = $this->entry('send_client', 'pending');
        $sent = $this->entry('send_client', 'sent');
        $failed = $this->entry('update_client', 'failed');

        Carbon::setTestNow('2026-08-15 12:00:00');

        $this->artisan('aade:reclaim-stale-outbox')->assertSuccessful();

        $this->assertSame('pending', $pending->fresh()->status->value);
        $this->assertSame('sent', $sent->fresh()->status->value);
        $this->assertSame('failed', $failed->fresh()->status->value);
    }

    /**
     * The exact row state a crash between claim() and the response leaves
     * behind: status flipped to processing, updated_at frozen at claim time.
     */
    private function abandonedEntry(string $operation, int $minutesAgo): OutboxEntry
    {
        Carbon::setTestNow(now()->subMinutes($minutesAgo));

        $entry = $this->entry($operation, 'processing');

        Carbon::setTestNow(now()->addMinutes($minutesAgo));

        return $entry;
    }

    private function entry(string $operation, string $status): OutboxEntry
    {
        return OutboxEntry::create([
            'local_entity_type' => 'work_order',
            'local_entity_id' => (string) random_int(1000, 999999),
            'operation' => $operation,
            'payload' => $operation === 'send_client'
                ? ['branch' => 1, 'useCase' => ['vehicleRegistrationNumber' => 'ΑΒΓ-1234']]
                : ['initialDclId' => 100000000830764, 'providedServiceCategory' => 3, 'entryCompletion' => true, 'invoiceKind' => 1],
            'payload_checksum' => hash('sha256', $operation.random_int(1, 999999)),
            'status' => $status,
            'attempts' => 1,
            'max_attempts' => 8,
            'next_retry_at' => null,
            'correlation_id' => CorrelationId::generate(),
            'dcl_id' => null,
        ]);
    }
}
