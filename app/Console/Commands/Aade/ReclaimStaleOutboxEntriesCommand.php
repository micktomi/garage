<?php

namespace App\Console\Commands\Aade;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Micktomi\GarageAadeBridge\Enums\DclOperation;
use Micktomi\GarageAadeBridge\Enums\FailureKind;
use Micktomi\GarageAadeBridge\Enums\OutboxStatus;
use Micktomi\GarageAadeBridge\Outbox\Models\OutboxEntry;

/**
 * Recovers outbox entries abandoned in `processing`.
 *
 * OutboxDispatcher::claim() flips an entry to `processing` and saves it before
 * making the HTTP call. A process that dies in between leaves the row there
 * permanently: aade-dcl:send-pending only selects `pending` and
 * aade-dcl:retry-failed only `failed`/`ambiguous`, so nothing else can ever
 * pick it up again.
 *
 * The lease clock is `updated_at`, not a dedicated column: while an entry is
 * `processing`, the claim itself is the only write that touches the row, so
 * updated_at *is* the claim time. If OutboxDispatcher ever gains another write
 * during that window, this command needs its own claimed_at column instead.
 */
final class ReclaimStaleOutboxEntriesCommand extends Command
{
    protected $signature = 'aade:reclaim-stale-outbox
        {--minutes=15 : How long an entry must have sat in processing before it counts as abandoned}';

    protected $description = 'Recover ΑΑΔΕ outbox entries left in "processing" by an interrupted dispatcher run.';

    public function handle(): int
    {
        $minutes = max(1, (int) $this->option('minutes'));

        $stale = OutboxEntry::query()
            ->where('status', OutboxStatus::Processing->value)
            ->where('updated_at', '<', now()->subMinutes($minutes))
            ->orderBy('id')
            ->get();

        if ($stale->isEmpty()) {
            $this->info('No stale processing entries.');

            return self::SUCCESS;
        }

        foreach ($stale as $entry) {
            $this->reclaim($entry, $minutes);
        }

        $this->info("Reclaimed {$stale->count()} stale entries.");

        return self::SUCCESS;
    }

    private function reclaim(OutboxEntry $entry, int $minutes): void
    {
        // SendClient has no idempotency key at ΑΑΔΕ, so we cannot know whether
        // the interrupted attempt landed. Resending could open a second
        // Digital Client List entry for the same vehicle — exactly what
        // FailureClassifier already refuses to risk on a timeout. Park it in
        // the same state it uses, which aade-dcl:retry-failed
        // --include-ambiguous can release after a RequestClients check.
        //
        // UpdateClient/CancelClient carry the dclId they act on, so replaying
        // them is idempotent and they can go straight back into the queue.
        $ambiguous = $entry->operation === DclOperation::SendClient;

        $entry->status = $ambiguous ? OutboxStatus::Ambiguous : OutboxStatus::Pending;
        $entry->failure_kind = $ambiguous ? FailureKind::Ambiguous : null;
        $entry->next_retry_at = null;

        if ($ambiguous) {
            $entry->last_error = "Dispatcher interrupted mid-flight: entry sat in processing for more than {$minutes} minutes. Whether ΑΑΔΕ recorded this submission is unknown — verify with RequestClients before requeuing.";
        }

        $entry->save();

        $this->line("  #{$entry->id} {$entry->operation->value} -> {$entry->status->value}");

        Log::warning('Reclaimed a stale ΑΑΔΕ outbox entry left in processing.', [
            'outbox_entry_id' => $entry->id,
            'operation' => $entry->operation->value,
            'reclaimed_as' => $entry->status->value,
            'local_entity_type' => $entry->local_entity_type,
            'local_entity_id' => $entry->local_entity_id,
        ]);
    }
}
