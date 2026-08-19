<?php

namespace Tests\Feature\Aade;

use App\Filament\Widgets\AadeOutboxHealthWidget;
use App\Models\User;
use App\Support\Aade\OutboxHealth;
use DOMDocument;
use DOMXPath;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Micktomi\GarageAadeBridge\Outbox\Models\OutboxEntry;
use Micktomi\GarageAadeBridge\Support\CorrelationId;
use Tests\TestCase;

/**
 * `failed` and `ambiguous` are terminal: no scheduled command in this app or
 * in garage-aade-bridge will ever move them on its own. Before this, nothing
 * said so — the row simply sat in a table nobody opens while ΑΑΔΕ believed a
 * vehicle was still in the shop.
 *
 * C-2 made that worse rather than better: the stale-lease reaper now *creates*
 * `ambiguous` entries by design, precisely because they need a human to check
 * with RequestClients whether the submission landed.
 */
class OutboxVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_no_existing_command_surfaces_a_stuck_entry(): void
    {
        $this->entry('send_client', 'ambiguous');
        $this->entry('update_client', 'failed');

        // Both of these report "nothing to do" and exit 0 — which is exactly
        // the invisibility this finding is about.
        $this->artisan('aade-dcl:send-pending')->assertSuccessful();

        $this->assertTrue(
            app(OutboxHealth::class)->needsAttention(),
            'Two terminal entries are sitting in the outbox and the pipeline is reporting success.',
        );
    }

    public function test_the_alert_command_reports_both_states_and_fails_the_run(): void
    {
        $ambiguous = $this->entry('send_client', 'ambiguous');
        $failed = $this->entry('update_client', 'failed');

        // Each expectation is matched against a single written line and
        // consumes the first one that contains it, so the tokens have to be
        // unambiguous: a bare "#2" also occurs inside "work_order#2…".
        $this->artisan('aade:outbox-alerts')
            ->expectsOutputToContain('ambiguous')
            ->expectsOutputToContain('failed')
            ->expectsOutputToContain('#'.$ambiguous->id.' send_client')
            ->expectsOutputToContain('#'.$failed->id.' update_client')
            ->assertFailed();
    }

    public function test_the_alert_command_spells_out_that_ambiguous_needs_reconciliation_not_a_retry(): void
    {
        $this->entry('send_client', 'ambiguous');

        $this->artisan('aade:outbox-alerts')
            ->expectsOutputToContain('RequestClients')
            ->assertFailed();
    }

    public function test_the_alert_command_never_moves_an_entry_itself(): void
    {
        $ambiguous = $this->entry('send_client', 'ambiguous');
        $failed = $this->entry('update_client', 'failed');

        $this->artisan('aade:outbox-alerts')->assertFailed();

        // Reporting is not reconciliation. A blind resend of an ambiguous
        // SendClient can open a second Digital Client List entry.
        $this->assertSame('ambiguous', $ambiguous->fresh()->status->value);
        $this->assertSame('failed', $failed->fresh()->status->value);
        $this->assertSame(0, $ambiguous->fresh()->transmissions()->count());
    }

    public function test_the_alert_command_is_silent_and_successful_on_a_clean_outbox(): void
    {
        $this->entry('send_client', 'sent');
        $this->entry('update_client', 'pending');

        $this->artisan('aade:outbox-alerts')->assertSuccessful();
    }

    public function test_the_alert_survives_an_unread_console_by_logging_a_warning(): void
    {
        $this->entry('send_client', 'ambiguous');

        Log::spy();

        $this->artisan('aade:outbox-alerts')->assertFailed();

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context = []): bool => str_contains($message, 'ΑΑΔΕ')
                && ($context['ambiguous'] ?? null) === 1);
    }

    public function test_the_alert_command_is_scheduled(): void
    {
        $commands = collect(app(Schedule::class)->events())
            ->map(fn ($event): string => (string) $event->command);

        $this->assertTrue(
            $commands->contains(fn (string $command): bool => str_contains($command, 'aade:outbox-alerts')),
            'An alert nobody runs is not visibility.',
        );
    }

    public function test_the_filament_dashboard_mounts_the_outbox_widget(): void
    {
        $this->entry('send_client', 'ambiguous');

        // Filament widgets are lazy, so the dashboard HTML carries the mount,
        // not the numbers — this half proves the widget is actually
        // registered on the panel rather than merely existing as a class.
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertOk()
            ->assertSee('aade-outbox-health', false);
    }

    public function test_the_outbox_widget_renders_both_counts(): void
    {
        $this->entry('send_client', 'ambiguous');
        $this->entry('update_client', 'failed');
        $this->entry('cancel_client', 'failed');
        $this->entry('send_client', 'sent');

        Livewire::actingAs(User::factory()->create())
            ->test(AadeOutboxHealthWidget::class)
            ->assertSee('ΑΑΔΕ: αμφίβολες εγγραφές')
            ->assertSee('ΑΑΔΕ: αποτυχημένες αποστολές')
            ->assertSeeInOrder(['ΑΑΔΕ: αμφίβολες εγγραφές', '1'])
            ->assertSeeInOrder(['ΑΑΔΕ: αποτυχημένες αποστολές', '2']);
    }

    public function test_the_workshop_dashboard_warns_when_entries_need_attention(): void
    {
        $this->entry('send_client', 'ambiguous');
        $this->entry('update_client', 'failed');

        $response = $this->actingAs(User::factory()->create())
            ->get(route('workshop.dashboard'))
            ->assertOk();

        $alerts = $this->alertNodes($response->getContent());

        $this->assertCount(1, $alerts);
        $this->assertStringContainsString('1 αμφίβολες', $alerts->item(0)->textContent);
        $this->assertStringContainsString('1 αποτυχημένες', $alerts->item(0)->textContent);
    }

    public function test_the_workshop_dashboard_stays_quiet_when_the_outbox_is_healthy(): void
    {
        $this->entry('send_client', 'sent');
        $this->entry('send_client', 'pending');

        $response = $this->actingAs(User::factory()->create())
            ->get(route('workshop.dashboard'))
            ->assertOk();

        // Scoped to the rendered element, not to the string anywhere in the
        // page — the stylesheet legitimately mentions the class every time.
        $this->assertCount(0, $this->alertNodes($response->getContent()));
    }

    private function alertNodes(string $html): \DOMNodeList
    {
        $document = new DOMDocument;
        @$document->loadHTML($html);

        return (new DOMXPath($document))->query(
            '//*[contains(concat(" ", normalize-space(@class), " "), " ws-aade-alert ")]'
        );
    }

    public function test_outbox_health_counts_only_terminal_states(): void
    {
        $this->entry('send_client', 'ambiguous');
        $this->entry('update_client', 'failed');
        $this->entry('cancel_client', 'failed');
        $this->entry('send_client', 'sent');
        $this->entry('send_client', 'pending');
        $this->entry('send_client', 'processing');
        $this->entry('send_client', 'cancelled');

        $health = app(OutboxHealth::class);

        $this->assertSame(['failed' => 2, 'ambiguous' => 1], $health->counts());
        $this->assertSame(3, $health->total());
        $this->assertTrue($health->needsAttention());
    }

    private function entry(string $operation, string $status): OutboxEntry
    {
        return OutboxEntry::create([
            'local_entity_type' => 'work_order',
            'local_entity_id' => (string) random_int(1000, 999999),
            'operation' => $operation,
            'payload' => ['branch' => 1, 'useCase' => ['vehicleRegistrationNumber' => 'ΑΒΓ-1234']],
            'payload_checksum' => hash('sha256', $operation.$status.random_int(1, 999999)),
            'status' => $status,
            'attempts' => 1,
            'max_attempts' => 8,
            'next_retry_at' => null,
            'correlation_id' => CorrelationId::generate(),
            'dcl_id' => null,
            'last_error' => in_array($status, ['failed', 'ambiguous'], true) ? 'Sample failure reason.' : null,
        ]);
    }
}
