<?php

namespace App\Console\Commands\Aade;

use App\Services\Aade\OutageResubmission;
use Illuminate\Console\Command;
use Micktomi\GarageAadeBridge\Outbox\Models\OutboxEntry;
use Throwable;

/**
 * The only path in this application that can set `transmissionFailure=1`.
 *
 * It exists as its own command, rather than as a flag on
 * aade-dcl:retry-failed, because the two questions it asks are decisions a
 * person has to make and be recorded as having made:
 *
 *   1. Does ΑΑΔΕ already have this submission? SendClient has no idempotency
 *      key, so resending one that landed opens a second Digital Client List
 *      entry for the same vehicle. Only a RequestClients lookup answers this.
 *   2. Was the ΑΑΔΕ link down when the original attempt was made? That, and
 *      only that, is what «Απώλεια διασύνδεσης» means (§5, Α.1057/2025 άρθρο
 *      5). "It went out late" is not the same claim and is not enough.
 *
 * Entry ids are always explicit — there is no "declare everything" mode,
 * because a blanket declaration is exactly the kind of unexamined filing this
 * command is meant to prevent.
 */
final class ResendAmbiguousCommand extends Command
{
    protected $signature = 'aade:resend-ambiguous
        {id* : Outbox entry id(s) to requeue}
        {--connection-lost= : yes|no — was the ΑΑΔΕ link down when the original attempt was made? "yes" sends transmissionFailure=1 with the real vehicle entry time}
        {--verified-not-received : Confirms you checked with RequestClients that ΑΑΔΕ has no entry for this submission}';

    protected $description = 'Requeue an ambiguous ΑΑΔΕ SendClient after manual verification, optionally declaring it as a connection-loss resubmission.';

    public function handle(OutageResubmission $outage): int
    {
        $entries = OutboxEntry::query()
            ->whereIn('id', array_map('intval', (array) $this->argument('id')))
            ->orderBy('id')
            ->get();

        if ($entries->isEmpty()) {
            $this->error('No outbox entries found for the given id(s).');

            return self::FAILURE;
        }

        $verified = $this->resolveVerification();

        if ($verified !== true) {
            $this->error('Aborted: resending a SendClient that ΑΑΔΕ already recorded creates a duplicate Digital Client List entry. Check with RequestClients first, then pass --verified-not-received.');

            return self::FAILURE;
        }

        $connectionLost = $this->resolveConnectionLost();

        if ($connectionLost === null) {
            $this->error('Aborted: --connection-lost must be answered with yes or no. It is the declaration itself, not a formality — «Απώλεια διασύνδεσης» is a statement about the cause, not about the delay.');

            return self::FAILURE;
        }

        $failed = 0;

        foreach ($entries as $entry) {
            try {
                $connectionLost
                    ? $outage->declareOutage($entry)
                    : $outage->requeueWithoutDeclaration($entry);

                $this->line(sprintf(
                    '  #%d requeued%s',
                    $entry->id,
                    $connectionLost ? ' — declared transmissionFailure=1 (creationDateTime '.$entry->payload['creationDateTime'].')' : '',
                ));
            } catch (Throwable $exception) {
                $failed++;
                $this->error('  #'.$entry->id.' '.$exception->getMessage());
            }
        }

        if ($failed > 0) {
            return self::FAILURE;
        }

        $this->info('Run php artisan aade-dcl:send-pending to dispatch them.');

        return self::SUCCESS;
    }

    private function resolveVerification(): bool
    {
        if ($this->option('verified-not-received')) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            return false;
        }

        return $this->confirm('Έχετε επιβεβαιώσει με RequestClients ότι η ΑΑΔΕ ΔΕΝ έχει αυτή την εγγραφή;', false);
    }

    /** @return bool|null null when the operator has not answered at all */
    private function resolveConnectionLost(): ?bool
    {
        $answer = $this->option('connection-lost');

        if ($answer === null) {
            if (! $this->input->isInteractive()) {
                return null;
            }

            return $this->confirm('Είχε πέσει η διασύνδεση με την ΑΑΔΕ όταν έγινε η αρχική προσπάθεια;', false);
        }

        return match (mb_strtolower(trim((string) $answer))) {
            'yes', 'y', 'ναι', '1', 'true' => true,
            'no', 'n', 'όχι', 'οχι', '0', 'false' => false,
            default => null,
        };
    }
}
