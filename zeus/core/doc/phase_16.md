# Phase 16 ✅

## Yêu cầu
Sử dụng SIDEBAR làm nơi quản lý username / account với yêu cầu sau:
• Sidebar là nơi DUY NHẤT hiển thị username
	+ Avatar
	+ Tên người dùng
	+ Email hoặc role
	+ Menu: Hồ sơ, Đổi mật khẩu, Đăng xuất
• Header KHÔNG hiển thị username
	+ Chỉ giữ icon avatar (không text)
	+ Icon avatar dùng để mở sidebar trên mobile
• Vị trí profile
	+ Đặt cuối sidebar
	+ Cố định, không scroll theo menu
• Mobile
	+ Sidebar dạng drawer
	+ Profile nằm cuối drawer
	+ Không hiển thị username ở header

## Giải pháp

### 1. Cấu trúc lại Sidebar
- Thêm `flex flex-col` vào aside để có thể fix profile ở cuối
- Bọc navigation trong `flex-1 overflow-y-auto` để menu có thể scroll
- Thêm User Profile Section ở cuối sidebar với `flex-shrink-0`

### 2. User Profile Section trong Sidebar
- Avatar với gradient background hoặc ảnh từ API
- Tên người dùng và role
- Menu dropdown mở lên trên (upward) với các options:
  + Hồ sơ (mở modal thông tin tài khoản)
  + Đổi mật khẩu (link đến admin.icanwork.vn)
  + Đăng xuất
- Khi sidebar collapsed, chỉ hiển thị avatar, click vẫn mở dropdown
- Account Modal giữ nguyên như trước

### 3. Header đơn giản hóa
- Xóa bỏ User Menu dropdown phức tạp
- Chỉ giữ avatar icon trên mobile (`md:hidden`)
- Click avatar sẽ mở sidebar (mobileMenuOpen = true)
- Không hiển thị username hay dropdown ở header

### 4. Responsive
- Desktop: Profile section luôn visible ở cuối sidebar
- Mobile: Profile section nằm trong drawer, hiển thị khi mở sidebar
- Avatar icon ở header chỉ hiển thị trên mobile

## Files đã sửa
- `src/resources/views/layouts/app.blade.php`
- `doc/phase_16.md`

