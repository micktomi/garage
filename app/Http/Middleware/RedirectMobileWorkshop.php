<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectMobileWorkshop
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local')) {
            return $next($request);
        }

        $userAgent = $request->userAgent() ?? '';
        $isPhone = preg_match('/iPhone|iPod|Windows Phone|Android.+Mobile/i', $userAgent) === 1;

        if ($isPhone) {
            return redirect('/admin');
        }

        return $next($request);
    }
}
