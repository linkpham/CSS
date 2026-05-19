# Phase 61 - Weekly Plan Export Enhancement

## Yêu cầu
- Tiếp tục chỉnh sửa kết quả từ phase 60: Người dùng không chọn range ngày mà chọn range tháng (ví dụ từ tháng 12/2025 đến tháng 3/2026; hoặc từ tháng 1/2026 đến tháng 12/2026, ....)
- Tổng ca dự kiến đang chạy cần đảm bảo gồm tổng ca (Schedule) và unscheduled (Những HV đang còn buổi chưa lên lịch)
- Format về màu sắc phải theo mẫu file `/Users/que/Downloads/Plan.xlsx`

## Thay đổi

### 1. Cập nhật `app/Services/WeeklyPlanService.php`

#### Thêm hằng số màu sắc theo Plan.xlsx:
- `COLOR_SPW_HEADER = 'E69138'` - Màu cam cho hàng SPW
- `COLOR_TOTAL_ROW = 'F6B26B'` - Màu cam nhạt cho hàng "Tổng ca dự kiến đang chạy"
- `COLOR_CLASS_SIZE = 'FCE5CD'` - Màu đào cho hàng class size (1v1, 1v2, 1v3, 1v8)

#### Cập nhật `getWeekSessionStats()`:
- Truy vấn riêng Scheduled sessions (ordles_status = 2) với lesson_starttime trong tuần
- Truy vấn riêng Unscheduled sessions (ordles_status = 1) từ orders đang active
- Unscheduled sessions được tính vào tuần đầu tiên của kỳ để tránh đếm trùng

#### Cập nhật `applyStyles()`:
- Row 4 (SPW): Background màu E69138 (cam)
- Row 5 (Tổng ca dự kiến): Background màu F6B26B (cam nhạt)
- Rows 6,11,16,21 (Class size Sum): Background màu FCE5CD (đào)

## Status: ✅ COMPLETED