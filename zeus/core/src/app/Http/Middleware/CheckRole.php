<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::guard('admin')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        $admin = Auth::guard('admin')->user();

        // Check if admin has any of the required roles (using Spatie)
        if ($admin->hasAnyRole($roles)) {
            return $next($request);
        }

        // Also check legacy role field for backwards compatibility
        $roleMapping = [
            'super-admin' => 'super_admin',
            'admin' => 'admin',
            'manager' => 'manager',
            'viewer' => 'viewer',
        ];

        foreach ($roles as $role) {
            $legacyRole = $roleMapping[$role] ?? $role;
            if ($admin->role === $legacyRole) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Forbidden. Insufficient permissions.'], 403);
        }

        abort(403, 'Bạn không có quyền truy cập trang này.');
    }
}
