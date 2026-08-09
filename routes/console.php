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

// Safety net for App\Listeners\Aade\SyncDeferredWorkOrderCompletion — see
// that class and SyncDeferredWorkOrderCompletionsCommand for why this exists
// alongside the event listener rather than instead of it.
Schedule::command('aade:sync-deferred-work-order-completions')->everyFiveMinutes()->withoutOverlapping();
