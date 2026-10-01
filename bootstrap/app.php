<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // The app has no route named 'login' — only Filament's admin panel login.
        // Redirect unauthenticated HTML requests to the Filament login page.
        // JSON/API requests receive 401 Unauthorized instead of a redirect.
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->expectsJson()) {
                return null; // triggers 401 AuthenticationException response
            }

            return '/admin/login';
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function ($response, $exception, Request $request) {
            if ($request->is('workshop', 'workshop/*') && $request->header('X-Inertia') && $exception instanceof AuthenticationException) {
                return Inertia::location('/admin/login');
            }
            $status = $response->getStatusCode();
            if ($request->is('workshop', 'workshop/*') && $status === 429) {
                return back(303)->withErrors(['rate_limit' => 'Έγιναν πολλά αιτήματα. Περιμένετε ένα λεπτό και δοκιμάστε ξανά.']);
            }
            if ($request->is('workshop', 'workshop/*') && in_array($status, [403, 404, 419, 500, 503])) {
                if ($status === 419) {
                    return back(303)->withErrors(['session' => 'Η συνεδρία έληξε. Ανανεώστε τη σελίδα και δοκιμάστε ξανά.']);
                }
                Inertia::setRootView('workshop-app');

                return Inertia::render('Error', ['status' => $status])->toResponse($request)->setStatusCode($status);
            }

            return $response;
        });
    })->create();
