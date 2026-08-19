<?php

namespace App\Support;

use RuntimeException;

/**
 * Fail-fast check for configuration that is harmless in development and
 * dangerous the moment APP_ENV=production.
 *
 * Both checks exist because .env is copied from .env.example and then edited
 * by hand: the two values below are exactly the ones whose *development*
 * setting produces no visible symptom in production. Debug pages look like a
 * normal error page until someone reads the stack trace; ΑΑΔΕ's test host
 * accepts every submission and returns success, so the garage looks compliant
 * while filing nothing.
 *
 * Deliberately no escape hatch: an environment variable that switches the
 * guard off is a variable someone can set by accident, which is the failure
 * mode this class exists to remove. A staging box that genuinely wants the
 * ΑΑΔΕ test host runs under APP_ENV=staging, not production.
 */
final class ProductionConfigGuard
{
    public static function enforce(): void
    {
        $violations = self::violations(
            (string) config('app.env'),
            (bool) config('app.debug'),
            config('aade-dcl.environment'),
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
    public static function violations(string $appEnv, bool $debug, mixed $aadeEnvironment): array
    {
        if ($appEnv !== 'production') {
            return [];
        }

        $violations = [];

        if ($debug) {
            $violations[] = 'APP_DEBUG must be false in production: debug error pages expose ΑΦΜ, plate numbers, customer data and credentials in stack traces.';
        }

        if ($aadeEnvironment !== 'production') {
            $violations[] = sprintf(
                'AADE_DCL_ENV must be "production" when APP_ENV=production, got %s: the ΑΑΔΕ test host accepts and acknowledges every submission, so the Digital Client List would silently stay empty.',
                is_string($aadeEnvironment) ? '"'.$aadeEnvironment.'"' : var_export($aadeEnvironment, true),
            );
        }

        return $violations;
    }
}
