<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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
        //
    })->create();
