# Phase 19 ✅

## Yêu cầu
- Cho phép tất cả các user có `admperm_value=1` trong bảng `tbl_admin_permissions` đều truy cập được dashboard.

## Giải pháp

### 1. Cập nhật AuthController
**File**: `src/app/Http/Controllers/Auth/AuthController.php`

**Thay đổi**:
- Xóa bỏ hằng số `ALLOWED_ROLE_IDS = [4, 5, 6]` - không còn giới hạn quyền truy cập theo role cố định
- Thay đổi logic kiểm tra quyền truy cập từ role-based sang permission-based:
  - **Trước**: Chỉ cho phép super admin (admin_id=1) hoặc users có role_id trong (4, 5, 6)
  - **Sau**: Cho phép super admin (admin_id=1) hoặc bất kỳ user nào có ít nhất một record với `admperm_value >= 1` trong bảng `tbl_admin_permissions`

**Logic mới**:
```php
// Check if admin is super admin (admin_id=1)
$isSuperAdmin = $admin->admin_id === self::SUPER_ADMIN_ID;

// Check if user has any permission with admperm_value >= 1 in tbl_admin_permissions
$hasPermission = DB::table('tbl_admin_permissions')
    ->where('admperm_admin_id', $admin->admin_id)
    ->where('admperm_value', '>=', 1)
    ->exists();

if (!$isSuperAdmin && !$hasPermission) {
    return back()->withErrors([...]);
}
```

## Files đã sửa
- `src/app/Http/Controllers/Auth/AuthController.php`
  - Xóa hằng số `ALLOWED_ROLE_IDS`
  - Thay đổi logic kiểm tra quyền truy cập dashboard
  - Cập nhật PHPDoc comments

- `doc/phase_19.md`
