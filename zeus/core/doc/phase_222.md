# Phase 222 ✅

- Trong `💤 Danh sách HV Inactive SpeakWell`, cần có chức lọc, tìm kiếm, sắp xếp các cột. Kiểm tra và sửa lỗi xem cái cột "Tổng còn lại" là phép tổng đã đúng chưa.

## Thực hiện

### 1. Sắp xếp các cột (Sort)
- Thêm clickable sort headers cho tất cả 7 cột: ID, Username, Tên HV, Email, Chưa lên lịch, Đã lên lịch, Tổng còn lại
- Backend: thêm `sort_by` và `sort_dir` params vào API `/api/csi/spw-inactive`
- Whitelist sort columns để tránh SQL injection
- Sort indicator (↑/↓) hiển thị trên cột đang sort
- Reset sort khi mở dialog

### 2. Tìm kiếm (Search)
- Đã có sẵn từ Phase 221: tìm theo ID, tên, email, username

### 3. Fix "Tổng còn lại"
- Backend: thêm computed column `remaining_total = (unscheduled_count + scheduled_count)` trong SQL
- Backend: cast `(int)` cho `unscheduled_count`, `scheduled_count`, `remaining_total`, `student_id` để đảm bảo JSON trả về kiểu number
- Frontend: sử dụng `parseInt()` để an toàn khi hiển thị và so sánh, tránh lỗi string concatenation
- Export Excel: cũng dùng `parseInt()` đảm bảo phép cộng đúng

### Files changed
- `src/app/Http/Controllers/CsiController.php` – thêm sort_by/sort_dir params
- `src/app/Services/CsiService.php` – sort logic + int casting
- `src/resources/views/csi/index.blade.php` – sortable headers + parseInt safety