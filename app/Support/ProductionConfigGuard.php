<?php

namespace App\Support;

use RuntimeException;

/**
 * Fail-fast check for configuration that is harmless in development and
 * dangerous the moment APP_ENV=production.
 *
 * The check exists because .env is copied from .env.example and then edited
 * by hand: APP_DEBUG is exactly the kind of value whose *development* setting
 * produces no visible symptom in production. Debug pages look like a normal
 * error page until someone reads the stack trace.
 *
 * Deliberately no escape hatch: an environment variable that switches the
 * guard off is a variable someone can set by accident, which is the failure
 * mode this class exists to remove.
 */
final class ProductionConfigGuard
{
    public static function enforce(): void
    {
        $violations = self::violations(
            (string) config('app.env'),
            (bool) config('app.debug'),
        );

        if ($violations === []) {
            return;
        }

        throw new RuntimeException(
            'Unsafe production configuration — refusing to boot. '.implode(' ', $violations)
        );
    }

    /**
     * Split from enforce() so the rules can be asserted directly, without a
     * test having to boot a real production container.
     *
     * @return list<string>
     */
    public static function violations(string $appEnv, bool $debug): array
    {
        if ($appEnv !== 'production') {
            return [];
        }

        $violations = [];

        if ($debug) {
            $violations[] = 'APP_DEBUG must be false in production: debug error pages expose ΑΦΜ, plate numbers, customer data and credentials in stack traces.';
        }

        return $violations;
    }
}
