<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use App\Support\ProductionConfigGuard;
use RuntimeException;
use Tests\TestCase;

/**
 * .env.example is what a deploy is copied from, so its defaults *are* the
 * production defaults until someone remembers to change them. The guard is
 * the second half of the same finding: remembering is not a control, so a
 * production boot on a development configuration has to fail loudly instead
 * of quietly serving stack traces or filing nothing with ΑΑΔΕ.
 */
class ProductionConfigSafetyTest extends TestCase
{
    public function test_env_example_ships_with_debug_off(): void
    {
        $this->assertMatchesRegularExpression(
            '/^APP_DEBUG=false$/m',
            $this->envExample(),
            'A deploy copied from .env.example would render stack traces containing ΑΦΜ, plate numbers and credentials.',
        );
    }

    public function test_env_example_ships_with_a_safe_log_configuration(): void
    {
        $envExample = $this->envExample();

        $this->assertDoesNotMatchRegularExpression(
            '/^LOG_LEVEL=debug$/m',
            $envExample,
            'debug-level logging writes request payloads (customer data) to disk on a production box.',
        );

        // warning is the floor, not a preference: every ΑΑΔΕ operational
        // signal this app emits (stuck outbox entries, unresolved dclIds,
        // reclaimed leases) is Log::warning. error would silence all of them.
        $this->assertMatchesRegularExpression('/^LOG_LEVEL=warning$/m', $envExample);

        $this->assertMatchesRegularExpression(
            '/^LOG_STACK=daily$/m',
            $envExample,
            'A single, never-rotated log file fills the disk of a long-running production box.',
        );
    }

    public function test_env_example_never_points_a_fresh_install_at_the_live_aade_host(): void
    {
        $this->assertMatchesRegularExpression(
            '/^AADE_DCL_ENV=test$/m',
            $this->envExample(),
            'A copied .env must not submit real Digital Client List entries from a developer machine.',
        );
    }

    public function test_production_refuses_to_boot_against_the_aade_test_environment(): void
    {
        $violations = ProductionConfigGuard::violations('production', false, 'test');

        $this->assertCount(1, $violations);
        $this->assertStringContainsString('AADE_DCL_ENV', $violations[0]);
    }

    public function test_production_refuses_to_boot_with_debug_enabled(): void
    {
        $violations = ProductionConfigGuard::violations('production', true, 'production');

        $this->assertCount(1, $violations);
        $this->assertStringContainsString('APP_DEBUG', $violations[0]);
    }

    public function test_a_correctly_configured_production_environment_passes(): void
    {
        $this->assertSame([], ProductionConfigGuard::violations('production', false, 'production'));
    }

    public function test_non_production_environments_are_left_alone(): void
    {
        $this->assertSame([], ProductionConfigGuard::violations('local', true, 'test'));
        $this->assertSame([], ProductionConfigGuard::violations('testing', true, null));
    }

    /**
     * The guard is worthless if nothing calls it: boot a fresh copy of the
     * application's own service provider against a production configuration
     * and require it to throw.
     */
    public function test_the_guard_is_wired_into_the_application_boot(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => true,
            'aade-dcl.environment' => 'test',
        ]);

        $this->expectException(RuntimeException::class);

        $this->app->register(new AppServiceProvider($this->app), force: true);
    }

    private function envExample(): string
    {
        return (string) file_get_contents(base_path('.env.example'));
    }
}
