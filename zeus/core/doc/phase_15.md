# Phase 15 ✅

## Yêu cầu
- Sửa lỗi khi truy cập dashboard sau khi login: `Attempt to read property "avatar" on null`

## Nguyên nhân
Trong file `app.blade.php`, User Menu section đang truy cập trực tiếp các thuộc tính của `auth()->guard('admin')->user()` như `->avatar`, `->name`, `->email`, `->initials`, `->role` mà không kiểm tra xem user có null hay không.

Khi có edge case (ví dụ: session hết hạn hoặc user chưa đăng nhập đúng cách), `auth()->guard('admin')->user()` trả về `null`, do đó khi cố gắng truy cập các thuộc tính sẽ gây ra lỗi PHP: "Attempt to read property 'avatar' on null".

Lỗi xảy ra tại `app.blade.php` dòng 939 trong phần User Menu của header.

## Giải pháp
1. **Bọc toàn bộ User Menu** trong `@auth('admin')...@endauth` directive để chỉ hiển thị khi user đã đăng nhập thành công
2. **Sử dụng PHP 8 null-safe operator `?->`** cho tất cả các lần truy cập thuộc tính user để đảm bảo an toàn tuyệt đối

## Thực hiện

### 1. Cập nhật layouts/app.blade.php
- Thêm `@auth('admin')` trước phần User Menu (dòng 937)
- Thêm `@endauth` sau khi đóng User Menu (dòng 1103)
- Thay thế tất cả `auth()->guard('admin')->user()->property` thành `auth()->guard('admin')->user()?->property` (null-safe operator)

### Các thuộc tính đã sửa với null-safe operator:
- `->avatar` (2 vị trí)
- `->initials` (2 vị trí)
- `->name` (4 vị trí)
- `->email` (2 vị trí)
- `->role` (2 vị trí)

## Files đã sửa
- `src/resources/views/layouts/app.blade.php`
- `doc/phase_15.md`
