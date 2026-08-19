<?php

namespace App\Providers;

use App\Models\User;
use App\Support\ProductionConfigGuard;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // First thing in boot(), so an unsafe production configuration
        // fails before any request, queue worker or artisan command can run
        // against it.
        ProductionConfigGuard::enforce();

        Carbon::setLocale('el');

        // The one capability that is not about a single record: what a part
        // cost and what it sells for is the garage's own margin data, and
        // it is the input to every profitability figure in the app.
        Gate::define('administer-pricing', static fn (User $user): bool => $user->isOwner());
    }
}
