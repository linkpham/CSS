# Phase 17 ✅

## Yêu cầu
- Vẫn chưa thấy phần hiển thị username ở dưới sidebar để thực hiện việc quản lý username. Hãy kiểm tra và sửa lỗi. 
- Chỉ có user: quenn, sonbn, hieulc, linhpg mới được thấy các câu lệnh SQL. 
- Chỉ có user: quenn, sonbn, hieulc, linhpg mới được thấy các chỉ số về doanh thu. 
- Rà soát và kiểm tra lại để đảm bảo tất cả các yêu cầu trên đều thực hiện. Nếu không đúng yêu cầu thì không được dừng lại.

## Giải pháp

### 1. Sửa lỗi hiển thị User Profile ở Sidebar
**Nguyên nhân**: Layout sử dụng `@auth('admin')` và `auth()->guard('admin')->user()` (Laravel Auth Guard) nhưng ứng dụng sử dụng session-based authentication (không dùng Laravel Auth).

**Giải pháp**: Thay đổi từ `@auth('admin')` sang `@if(session('admin_authenticated'))` và sử dụng session variables:
- `session('admin_name')` thay cho `auth()->guard('admin')->user()->name`
- `session('admin_email')` thay cho `auth()->guard('admin')->user()->email`
- `session('admin_role')` thay cho `auth()->guard('admin')->user()->role`

### 2. Giới hạn hiển thị SQL Queries
**Giải pháp**: 
- Thêm session variable `can_view_sql` khi đăng nhập
- Thêm CSS class `hide-sql` hoặc `can-view-sql` vào body tag dựa trên session
- Sử dụng CSS để ẩn `.tooltip-sql` và `code` trong tooltip khi có class `hide-sql`

```css
.hide-sql .tooltip-sql,
.hide-sql .tooltip-content code {
    display: none !important;
}
```

### 3. Giới hạn hiển thị Doanh thu
**Giải pháp**:
- Cập nhật logic trong AuthController để chỉ set `can_view_revenue = true` cho users trong danh sách đặc quyền
- Danh sách privileged users: `['quenn', 'sonbn', 'hieulc', 'linhpg']`
- Sidebar menu Revenue chỉ hiển thị khi `session('can_view_revenue')` = true

## Files đã sửa
- `src/app/Http/Controllers/Auth/AuthController.php`
  - Thêm constant `PRIVILEGED_USERNAMES`
  - Cập nhật logic login để set `can_view_sql` và `can_view_revenue` chỉ cho privileged users
  - Cập nhật logout để xóa session `can_view_sql`
  
- `src/resources/views/layouts/app.blade.php`
  - Thay đổi User Profile Section từ `@auth('admin')` sang `@if(session('admin_authenticated'))`
  - Thay đổi tất cả references từ `auth()->guard('admin')->user()` sang session variables
  - Thêm CSS class conditional `hide-sql` hoặc `can-view-sql` vào body tag
  - Thêm CSS rules để ẩn SQL tooltips khi user không có quyền

- `doc/phase_17.md`
