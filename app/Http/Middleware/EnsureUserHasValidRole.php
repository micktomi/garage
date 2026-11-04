<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** /workshop counterpart of User::canAccessPanel(): only owner/staff accounts get in. */
class EnsureUserHasValidRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->hasValidRole(), 403);

        return $next($request);
    }
}
