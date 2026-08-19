<?php

namespace App\Console\Commands\Aade;

use App\Support\Aade\OutboxHealth;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Micktomi\GarageAadeBridge\Enums\OutboxStatus;
use Micktomi\GarageAadeBridge\Outbox\Models\OutboxEntry;

/**
 * Says out loud that ΑΑΔΕ outbox entries are stuck, because nothing else does.
 *
 * aade-dcl:send-pending and aade-dcl:retry-failed both exit 0 while `failed`
 * and `ambiguous` rows pile up behind them — they simply have nothing to
 * select. This command reports and never repairs: exit code 1 so the
 * scheduler/cron surfaces it, a Log::warning so the signal survives an unread
 * console, and the entry list so the person reading it knows which work
 * orders are affected.
 *
 * Reporting only, deliberately. Requeuing an ambiguous SendClient without a
 * RequestClients check can open a second Digital Client List entry for a
 * vehicle ΑΑΔΕ already knows about.
 */
final class OutboxAlertsCommand extends Command
{
    protected $signature = 'aade:outbox-alerts
        {--limit=20 : Maximum entries to list per state}';

    protected $description = 'Report ΑΑΔΕ outbox entries stuck in failed/ambiguous, which no scheduled command will ever resolve.';

    public function handle(OutboxHealth $health): int
    {
        $counts = $health->counts();
        $limit = max(1, (int) $this->option('limit'));
        $stalled = $health->stalledSendClients(limit: $limit);

        if (array_sum($counts) === 0 && $stalled->isEmpty()) {
            $this->info('ΑΑΔΕ outbox is clean: no failed, ambiguous or unattempted entries.');

            return self::SUCCESS;
        }

        $this->error(sprintf(
            '%d ΑΑΔΕ outbox entries need a human: %d ambiguous, %d failed, %d never attempted.',
            array_sum($counts) + $stalled->count(),
            $counts['ambiguous'],
            $counts['failed'],
            $stalled->count(),
        ));

        if ($counts['ambiguous'] > 0) {
            $this->newLine();
            $this->line('ambiguous — the submission may already exist at ΑΑΔΕ.');
            $this->line('  Verify each one with a RequestClients lookup before deciding. Only after that,');
            $this->line('  release it with: php artisan aade:resend-ambiguous <id> --connection-lost=yes|no --verified-not-received');
            $this->line('  (--connection-lost=yes is what files it as «Απώλεια διασύνδεσης» with the real entry time.)');
            $this->line('  Never requeue blindly: SendClient has no idempotency key, so a resend can open');
            $this->line('  a second Digital Client List entry for the same vehicle.');
            $this->listEntries($health->entriesInState(OutboxStatus::Ambiguous, $limit));
        }

        if ($counts['failed'] > 0) {
            $this->newLine();
            $this->line('failed — retries exhausted or permanently rejected.');
            $this->line('  Fix the cause shown below, then: php artisan aade-dcl:retry-failed');
            $this->listEntries($health->entriesInState(OutboxStatus::Failed, $limit));
        }

        if ($stalled->isNotEmpty()) {
            $this->newLine();
            $this->line(sprintf(
                'never attempted — no transmission recorded in over %d minutes, so nothing is dispatching.',
                OutboxHealth::DISPATCHER_STALL_MINUTES,
            ));
            $this->line('  Check that the scheduler is running: php artisan schedule:list, then aade-dcl:send-pending.');
            $this->line('  These are NOT marked as «Απώλεια διασύνδεσης» automatically — nothing here knows whether the');
            $this->line('  ΑΑΔΕ link was down or our own dispatcher was. If it was the link, declare it per entry with:');
            $this->line('  php artisan aade:resend-ambiguous <id> --connection-lost=yes --verified-not-received');
            $this->listEntries($stalled);
        }

        Log::warning('ΑΑΔΕ outbox entries are stuck and will not resolve on their own.', [
            'ambiguous' => $counts['ambiguous'],
            'failed' => $counts['failed'],
            'never_attempted' => $stalled->count(),
        ]);

        return self::FAILURE;
    }

    /** @param  Collection<int, OutboxEntry>  $entries */
    private function listEntries($entries): void
    {
        foreach ($entries as $entry) {
            $this->line(sprintf(
                '  #%d %s %s#%s%s',
                $entry->id,
                $entry->operation->value,
                $entry->local_entity_type,
                $entry->local_entity_id,
                $entry->last_error === null ? '' : ' — '.str($entry->last_error)->limit(160),
            ));
        }
    }
}
