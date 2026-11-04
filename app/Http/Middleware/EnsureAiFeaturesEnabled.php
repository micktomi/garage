<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hides the Gemini-backed workshop features (assistant, registration scan)
 * entirely while AI_ASSISTANT_ENABLED is false.
 */
class EnsureAiFeaturesEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('garage-assistant.enabled'), 404);

        return $next($request);
    }
}
