<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthenticate
{
    /**
     * Handle an incoming request.
     * 
     * This middleware checks if the user is authenticated and extends
     * the session lifetime on each request to prevent session expiration
     * while the user is actively using the system.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('admin_authenticated')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login');
        }

        // Extend session lifetime on each authenticated request
        // This prevents session expiration while user is actively using the system
        $this->extendSession($request);

        return $next($request);
    }

    /**
     * Extend the session lifetime on activity.
     * 
     * This ensures that active users don't get logged out due to session expiration.
     * The session will only expire after inactivity for the configured lifetime.
     */
    protected function extendSession(Request $request): void
    {
        // Update last activity timestamp
        session(['admin_last_activity' => now()->toDateTimeString()]);

        // Regenerate session ID periodically (every 30 minutes) for security
        // but not on every request to avoid CSRF issues
        $lastRegenerated = session('session_regenerated_at');
        $shouldRegenerate = !$lastRegenerated || 
            now()->diffInMinutes(\Carbon\Carbon::parse($lastRegenerated)) >= 30;

        if ($shouldRegenerate && !$request->expectsJson()) {
            // Only regenerate session ID, keep the CSRF token
            $token = $request->session()->token();
            $request->session()->regenerate();
            $request->session()->put('_token', $token);
            session(['session_regenerated_at' => now()->toDateTimeString()]);
        }
    }
}
