# Phase 79 - Fix Excel Export: Scheduled Sheet Data Mismatch & Second Click Issue

## Yêu cầu
- Kiểm tra đảm bảo không có sự sai sót trong bảng dữ liệu `🌍 Tổng số ca Scheduled` và tính năng xuất dữ liệu trên trang `/daily-ops?program=speakwell`. Kết quả bảng dữ liệu `🌍 Tổng số ca Scheduled` chuẩn xác, tuy nhiên dữ liệu trong sheet Scheduled khi xuất dữ liệu không chính xác giống như bảng dữ liệu `🌍 Tổng số ca Scheduled`
- Lỗi khi nhấn nút `Xuất dữ liệu` lần thứ 2 (không thấy trả về file dữ liệu). Kiểm tra thật kỹ, không được dừng nếu còn sai sót.

## Phân tích
1. **Scheduled sheet data mismatch**: 
   - UI table "🌍 Tổng số ca Scheduled" sử dụng `getTeacherCountryWeeklyWithClassSize()`
   - Excel export sử dụng `generatePlanReport()` → `getWeekSessionStats()` với query khác
   - Cần thống nhất cả hai sử dụng cùng một data source

2. **Second export click not working**:
   - `window.location.href = url` không trigger download mới nếu URL giống nhau
   - Cần sử dụng approach tạo dynamic link element để force download

## Thay đổi

### 1. WeeklyPlanService.php
- Thêm method `buildScheduledSheetFromApi()` sử dụng data từ `getTeacherCountryWeeklyWithClassSize()`
- Cập nhật `generateExcel()` để sử dụng `getTeacherCountryWeeklyWithClassSize()` thay vì `generatePlanReport()`

### 2. daily-ops.blade.php
- Cập nhật `exportPlan()` function để sử dụng dynamic link element thay vì `window.location.href`
- Link được tạo, click programmatically, và remove ngay sau khi click

## Kết quả
- ✅ Sheet Scheduled trong Excel export giờ hiển thị dữ liệu giống hệt UI table
- ✅ Nhấn nút "Xuất dữ liệu" nhiều lần đều hoạt động bình thường

