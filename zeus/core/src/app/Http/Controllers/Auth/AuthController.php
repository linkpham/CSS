<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Super admin ID that bypasses role check
     * admin_id=1 has full system access
     */
    private const SUPER_ADMIN_ID = 1;
    
    /**
     * Role ID that can view revenue data
     * 5 = Kế toán (Accountant)
     */
    public const ACCOUNTANT_ROLE_ID = 5;

    /**
     * Role IDs that can view Teacher Management page
     * Phase 214: Expanded to include roles matching huyentdt & giangctt permissions.
     * 3 = CS (Student Affairs), 4 = Xem thông tin GV, 6 = Admin Vận hành,
     * 7 = Gia hạn KH, 9 = Tuyển dụng GV, 11 = QTGV,
     * 14 = Quản trị CSS, 29 = Quản lý Giáo viên
     */
    public const TEACHER_MGMT_ROLE_IDS = [3, 4, 6, 7, 9, 11, 14, 29];

    /**
     * Usernames that can view SQL queries and revenue data
     * Only these users can see SQL tooltips and revenue metrics
     */
    private const PRIVILEGED_USERNAMES = ['quenn', 'sonbn', 'hieulc', 'linhpg'];

    /**
     * Additional usernames that can view revenue data (but not SQL queries)
     */
    private const REVENUE_USERNAMES = ['giangdth2', 'nhaidh', 'anhptn3'];

    /**
     * Display the login view.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (session('admin_authenticated')) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     * Authenticates against tbl_admin table in Zeus Core database.
     * Uses MD5 hash for password verification (matching existing Zeus Core system).
     * 
     * Authorization requirements:
     * - Only admin_active=1 users can login
     * - Super admin (admin_id=1) can always login
     * - Any user with at least one permission record (admperm_value >= 1) in tbl_admin_permissions can login
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        try {
            // Query tbl_admin table for the username
            $admin = DB::table('tbl_admin')
                ->where('admin_username', $credentials['username'])
                ->first();

            // Check if admin exists and password matches (salted MD5 hash - same as Zeus Core)
            $passwordSalt = env('PASSWORD_SALT', 'ewgfhgfhgfhgkuyajflfdsaf');
            $hashedPassword = md5($passwordSalt . $credentials['password'] . $passwordSalt);
            
            if ($admin && $admin->admin_password === $hashedPassword) {
                // Check if admin is active
                if (!$admin->admin_active) {
                    return back()->withErrors([
                        'username' => 'Tài khoản đã bị vô hiệu hóa. Vui lòng liên hệ quản trị viên.',
                    ])->withInput($request->only('username', 'remember'));
                }

                // Get admin roles from tbl_admin_roles
                $adminRoles = DB::table('tbl_admin_roles')
                    ->where('admrole_admin_id', $admin->admin_id)
                    ->pluck('admrole_role_id')
                    ->toArray();

                // Check if admin is super admin (admin_id=1)
                $isSuperAdmin = $admin->admin_id === self::SUPER_ADMIN_ID;
                
                // Check if user has any permission with admperm_value >= 1 in tbl_admin_permissions
                $hasPermission = DB::table('tbl_admin_permissions')
                    ->where('admperm_admin_id', $admin->admin_id)
                    ->where('admperm_value', '>=', 1)
                    ->exists();
                
                if (!$isSuperAdmin && !$hasPermission) {
                    return back()->withErrors([
                        'username' => 'Bạn không có quyền truy cập hệ thống Dashboard. Vui lòng liên hệ quản trị viên.',
                    ])->withInput($request->only('username', 'remember'));
                }

                // Check if user is in the privileged users list (quenn, sonbn, hieulc, linhpg)
                $isPrivilegedUser = in_array($admin->admin_username, self::PRIVILEGED_USERNAMES);
                
                // Only privileged users can view SQL queries
                $canViewSql = $isPrivilegedUser;
                // Privileged users + revenue-specific users can view revenue data
                $canViewRevenue = $isPrivilegedUser || in_array($admin->admin_username, self::REVENUE_USERNAMES);
                
                // Phase 214: Users with teacher-related roles (3,4,6,7,9,11,14,29) + privileged users can view Teacher Management
                $canViewTeacherMgmt = $isPrivilegedUser || !empty(array_intersect($adminRoles, self::TEACHER_MGMT_ROLE_IDS));

                // Get role names for display
                $roleNames = DB::table('tbl_roles_lang')
                    ->whereIn('rolelang_role_id', $adminRoles)
                    ->where('rolelang_lang_id', 2) // Vietnamese language
                    ->pluck('role_name')
                    ->toArray();
                
                // Fallback to English if no Vietnamese names found
                if (empty($roleNames)) {
                    $roleNames = DB::table('tbl_roles_lang')
                        ->whereIn('rolelang_role_id', $adminRoles)
                        ->where('rolelang_lang_id', 1) // English language
                        ->pluck('role_name')
                        ->toArray();
                }

                // Preserve the CSRF token before regenerating session
                $csrfToken = $request->session()->token();
                
                // Regenerate session ID for security
                $request->session()->regenerate();
                
                // Restore the CSRF token
                $request->session()->put('_token', $csrfToken);
                
                // Store authentication state in session
                session([
                    'admin_authenticated' => true,
                    'admin_id' => $admin->admin_id,
                    'admin_email' => $admin->admin_email,
                    'admin_name' => $admin->admin_name,
                    'admin_username' => $admin->admin_username,
                    'admin_role_ids' => $adminRoles,
                    'admin_role_names' => $roleNames,
                    'admin_role' => !empty($roleNames) ? implode(', ', $roleNames) : 'Admin',
                    'can_view_sql' => $canViewSql,
                    'can_view_revenue' => $canViewRevenue,
                    'can_view_teacher_mgmt' => $canViewTeacherMgmt,
                    'admin_login_at' => now()->toDateTimeString(),
                    'session_regenerated_at' => now()->toDateTimeString(),
                ]);

                return redirect()->intended(route('dashboard'));
            }

            return back()->withErrors([
                'username' => 'Thông tin đăng nhập không chính xác.',
            ])->withInput($request->only('username', 'remember'));

        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            
            return back()->withErrors([
                'username' => 'Không thể kết nối đến cơ sở dữ liệu. Vui lòng thử lại.',
            ])->withInput($request->only('username', 'remember'));
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function logout(Request $request): RedirectResponse
    {
        // Clear admin session
        session()->forget([
            'admin_authenticated', 
            'admin_id', 
            'admin_email', 
            'admin_name', 
            'admin_username', 
            'admin_role',
            'admin_role_ids',
            'admin_role_names',
            'can_view_sql',
            'can_view_revenue',
            'can_view_teacher_mgmt',
            'admin_login_at',
            'session_regenerated_at',
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
