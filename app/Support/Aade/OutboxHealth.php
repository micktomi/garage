<?php

namespace App\Support\Aade;

use Illuminate\Database\Eloquent\Collection;
use Micktomi\GarageAadeBridge\Enums\DclOperation;
use Micktomi\GarageAadeBridge\Enums\OutboxStatus;
use Micktomi\GarageAadeBridge\Outbox\Models\OutboxEntry;

/**
 * The two ΑΑΔΕ outbox states nothing in this system will ever resolve on its
 * own, and what each one means for the person who has to resolve them.
 *
 * `failed`    — retries exhausted, or a permanent rejection. The cause is
 *               local (bad payload, wrong credentials, ΑΑΔΕ validation) and
 *               `aade-dcl:retry-failed` can requeue it once fixed.
 * `ambiguous` — the SendClient may or may not have reached ΑΑΔΕ. ΑΑΔΕ has no
 *               idempotency key for SendClient, so resending risks a second
 *               Digital Client List entry for the same vehicle. Only a
 *               RequestClients reconciliation can decide, which is why nothing
 *               here retries it.
 *
 * Read-only on purpose: this is the shared source for the alert command, the
 * Filament widget and the workshop banner, and none of the three may become a
 * place where entries quietly change state.
 */
final class OutboxHealth
{
    /**
     * How long a SendClient may sit without a single recorded attempt before
     * we conclude that nothing is trying — aade-dcl:send-pending is scheduled
     * every minute, so silence this long means the dispatcher, the scheduler
     * or the machine was not running.
     *
     * Operational, not regulatory. It never sets transmissionFailure: "we
     * never tried" is evidence that our own pipeline stopped, not that the
     * ΑΑΔΕ link was down, and the specification gives no duration that turns
     * a late submission into «Απώλεια διασύνδεσης». It only decides when to
     * say so out loud. Deliberately the same window as
     * aade:reclaim-stale-outbox, which detects the neighbouring failure.
     */
    public const DISPATCHER_STALL_MINUTES = 15;

    /** @return array{failed: int, ambiguous: int} */
    public function counts(): array
    {
        $counts = OutboxEntry::query()
            ->whereIn('status', [OutboxStatus::Failed->value, OutboxStatus::Ambiguous->value])
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'failed' => (int) $counts->get(OutboxStatus::Failed->value, 0),
            'ambiguous' => (int) $counts->get(OutboxStatus::Ambiguous->value, 0),
        ];
    }

    public function total(): int
    {
        return array_sum($this->counts());
    }

    public function needsAttention(): bool
    {
        return OutboxEntry::query()
            ->whereIn('status', [OutboxStatus::Failed->value, OutboxStatus::Ambiguous->value])
            ->exists();
    }

    /**
     * SendClients that no attempt has ever been made for. Invisible to
     * everything else: they are `pending` and perfectly well-formed, they
     * simply were never picked up.
     *
     * @return Collection<int, OutboxEntry>
     */
    public function stalledSendClients(?int $minutes = null, int $limit = 20): Collection
    {
        return OutboxEntry::query()
            ->where('operation', DclOperation::SendClient->value)
            ->where('status', OutboxStatus::Pending->value)
            ->where('created_at', '<', now()->subMinutes($minutes ?? self::DISPATCHER_STALL_MINUTES))
            ->whereDoesntHave('transmissions')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    /** @return Collection<int, OutboxEntry> */
    public function entriesInState(OutboxStatus $status, int $limit = 20): Collection
    {
        return OutboxEntry::query()
            ->where('status', $status->value)
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }
}
