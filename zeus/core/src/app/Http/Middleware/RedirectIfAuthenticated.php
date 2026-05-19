<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        // Check session-based authentication
        if (session('admin_authenticated')) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
