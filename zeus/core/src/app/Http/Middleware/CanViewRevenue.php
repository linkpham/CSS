<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to check if the authenticated user can view revenue data.
 * Only users with role_id = 5 (Accountant/Kế toán) are allowed.
 * This is set in the session during login (see AuthController).
 */
class CanViewRevenue
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!session('admin_authenticated')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        // Check if user has permission to view revenue
        if (!session('can_view_revenue')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xem dữ liệu doanh thu. Chỉ Kế toán mới được phép truy cập.',
                ], 403);
            }
            
            // For web requests, redirect to dashboard with error message
            return redirect()->route('dashboard')->with('error', 'Bạn không có quyền xem dữ liệu doanh thu. Chỉ Kế toán (role ID=5) mới được phép truy cập.');
        }

        return $next($request);
    }
}
