<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// garage-aade-bridge ships this command but does not schedule it itself
// (see its README, "Laravel scheduler example") — no schedule entry for it
// existed anywhere in this repo before this line.
Schedule::command('aade-dcl:send-pending')->everyMinute()->withoutOverlapping();

// Recovers entries abandoned in `processing` by an interrupted send-pending
// run — nothing else in the pipeline can see them. See the command for why the
// stale window is measured on updated_at.
Schedule::command('aade:reclaim-stale-outbox')->everyFiveMinutes()->withoutOverlapping();

// `failed` and `ambiguous` are terminal — send-pending and retry-failed both
// exit 0 while they pile up. Hourly is deliberate: this reports, it never
// repairs, so running it more often only produces more identical noise.
Schedule::command('aade:outbox-alerts')->hourly()->withoutOverlapping();

// Safety net for App\Listeners\Aade\SyncDeferredWorkOrderCompletion — see
// that class and SyncDeferredWorkOrderCompletionsCommand for why this exists
// alongside the event listener rather than instead of it.
Schedule::command('aade:sync-deferred-work-order-completions')->everyFiveMinutes()->withoutOverlapping();
