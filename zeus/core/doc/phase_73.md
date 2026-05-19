# Phase 73 - Tiếp tục thực hiện yêu cầu mới

## Yêu cầu
- Block `🌍 Tổng số ca theo Quốc gia GV (Tuần)` phải được hiển thị theo đúng cấu trúc giống Format về màu sắc phải theo mẫu file `/Users/que/Downloads/Plan.xlsx`. Kiểm tra với kết quả xuất ra từ file `Xuất Plan` để đảm bảo chính xác. Không được phép sai sót
- Sửa tên Sheet `Plan` trong file excel được xuất từ `Xuất Plan` thành Scheduled, sửa tên sheet `Teacher Country` trong trong file excel được xuất từ `Xuất Plan` thành `Unscheduled`.

## Thực hiện ✅

### 1. Đổi tên Sheet Excel
- `src/app/Services/WeeklyPlanService.php`:
  - Sheet "Plan" → "Scheduled"
  - Sheet "Teacher Country" → "Unscheduled"

### 2. Cập nhật màu sắc Dashboard theo Plan.xlsx
- `src/resources/views/dashboard/daily-ops.blade.php`:
  - Header row: Peach (#FCE5CD) - giống class size headers trong Plan.xlsx
  - Total row: Light orange (#F6B26B) - giống Tổng ca dự kiến row
  - TỔNG column header: Orange (#E69138) - giống SPW header
  - Data rows: White background với hover effect

### Màu sắc theo Plan.xlsx:
| Thành phần | Màu | Hex Code |
|------------|-----|----------|
| SPW Header | Orange | #E69138 |
| Total Row | Light Orange | #F6B26B |
| Class Size Header | Peach | #FCE5CD |
| Data Rows | White | #FFFFFF | 


