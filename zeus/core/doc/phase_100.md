# Phase 100

## Yêu cầu
- Trong bảng `📋 Mã Acceptance Code (Mã kết quả ca học)` của trang KPI, cần hiện ra: Số lượng các Giáo viên sẽ bị phạt (mã code=1, 2, 3, 6,14, 17) kèm danh sách (có thông tin giáo viên, ca học,  mã lỗi và) và cho phép export ra excel. Bổ sung bảng `📋 Mã Acceptance Code (Mã kết quả ca học)` vào cả trang Quảng trị GV `/teacher-management?program=speakwell`

## Thực hiện

### Backend (DashboardService)
- Thêm constant `PENALTY_CODES = [1, 2, 3, 6, 14, 17]` — mã acceptance code GV bị phạt
- Mở rộng `getAcceptanceCodeBreakdown()`: thêm `penalized_sessions` và `penalized_teachers` (unique GV)
  - Codes 1, 2, 3, 6: query từ completed sessions (status=3)
  - Codes 14, 17: query từ cancelled sessions (status=4)
- Thêm method `getPenalizedTeachersDetails(string $period)`: trả danh sách chi tiết GV bị phạt
  - Thông tin: GV (tên, email), HV (tên), ngày giờ, thời lượng, mã lỗi, mô tả, trạng thái

### Backend (DashboardController)
- Thêm `apiPenalizedTeachersDetails()` — API endpoint
- Truyền `acceptanceCodeStats` và `acceptanceCodesList` vào trang teacher-management

### Routes
- Thêm route `GET /api/penalized-teachers-details`

### Frontend — Trang KPI (index.blade.php)
- Thêm card "GV bị phạt" (màu cam) vào grid summary mỗi period tab (grid 4→5 cols)
  - Hiển thị số GV unique bị phạt, tooltip hiện tổng ca
  - Clickable → mở modal chi tiết
- Thêm modal "Danh sách GV bị phạt" (10 cột: #, GV, Email, HV, Ngày, Giờ, Thời lượng, Mã lỗi, Mô tả, Trạng thái)
- Nút "Xuất Excel" — export CSV UTF-8 BOM
- Alpine.js function `penalizedTeachersSection()` quản lý state

### Frontend — Trang Quản trị GV (teacher-management.blade.php)
- Bổ sung toàn bộ section `📋 Mã Acceptance Code` cho SpeakWell tab
  - Dùng `@foreach` loop qua các period tabs (DRY, không lặp code)
  - Bao gồm: summary cards, code breakdown table, code lookup modal, penalized modal + export
- Alpine.js function `penalizedTeachersMgmtSection()` quản lý state

## Status: ✅ Hoàn thành
