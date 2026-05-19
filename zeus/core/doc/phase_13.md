# Phase 13 ✅

## Yêu cầu
- Bổ sung thêm sub menu Tài khoản, Đổi mật khẩu tại nút bấm tài khoản (có avatar). Khi click vào menu Tài khoản sẽ mở ra modal chứa Tên người dùng, Email, Họ và Tên, Múi giờ, Avatar. Khi click vào nút Đổi mật khẩu thì redirect sang trang: https://admin.icanwork.vn/profile/change-password

- Fix lỗi hiển thị: 
```
	•	Thanh scroll ngang xuất hiện
	•	Nội dung vẫn nằm trong khung nhưng có thể kéo sang phải
```

## Thực hiện

### 1. Cập nhật User Menu (layouts/app.blade.php)
- Thêm Alpine.js state `showAccountModal` để điều khiển modal
- Hiển thị avatar thực nếu có, fallback về initials nếu không có
- Thêm menu item "Tài khoản" với icon user
- Thêm menu item "Đổi mật khẩu" với icon key và external link indicator
- Thêm divider trước nút đăng xuất
- Cải thiện styling với SVG icons thay vì emoji

### 2. Tạo Account Modal
- Modal overlay với backdrop blur
- Hiển thị thông tin tài khoản:
  - Avatar lớn (24x24) hoặc initials với gradient
  - Tên người dùng
  - Email
  - Họ và Tên
  - Múi giờ (Asia/Ho_Chi_Minh UTC+7)
  - Vai trò
- Footer với nút "Đổi mật khẩu" (redirect sang admin.icanwork.vn) và "Đóng"

### 3. Fix Horizontal Scroll
- Thêm `overflow-hidden` vào flex container chính (`div.flex.h-screen`)
- Thêm `overflow-x-hidden` và `min-w-0` vào main element
- Thêm CSS rules mới:
  - `.flex.h-screen`: max-width: 100vw, overflow-x: hidden
  - `main`: max-width: 100%, overflow-x: hidden
  - `.p-3, .p-6`: max-width: 100%, box-sizing: border-box

## Files đã sửa
- `src/resources/views/layouts/app.blade.php`
- `doc/phase_13.md`