<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectDesktopUsers
{
    public function handle(Request $request, Closure $next)
    {
        $userAgent = $request->header('User-Agent');

        // Simple mobile detection
        $isMobile = preg_match('/Mobile|Android|iPhone|iPad/i', $userAgent);

        if (!$isMobile) {
            // Desktop user → redirect to admin login
            return redirect('/login');
        }

        return $next($request);
    }
}