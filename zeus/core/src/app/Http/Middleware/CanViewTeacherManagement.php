<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to check if the authenticated user can view the Teacher Management page.
 * Phase 214: Users with role_id 3,4,6,7,9,11,14,29 (or privileged users) are allowed.
 * This is set in the session during login (see AuthController).
 */
class CanViewTeacherManagement
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

        // Check if user has permission to view teacher management
        if (!session('can_view_teacher_mgmt')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền truy cập trang Quản trị GV.',
                ], 403);
            }
            
            // For web requests, redirect to dashboard with error message
            return redirect()->route('dashboard')->with('error', 'Bạn không có quyền truy cập trang Quản trị GV. Vui lòng liên hệ quản trị viên để được cấp quyền.');
        }

        return $next($request);
    }
}
