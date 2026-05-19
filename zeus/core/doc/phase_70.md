# Phase 70 - Fix Week Calculation (First Monday Rule)

## Yêu cầu
- Sửa lỗi sai của phase 69
- Tuần được tính từ thứ 2 đến chủ nhật. Tuần đầu tiên của năm phải tính từ thứ 2 đầu tiên của năm
- Ví dụ năm 2026: Tuần 1 = 02Jan2026 (Mon) đến 08Jan2026 (Sun)

## Thay đổi

### `src/app/Services/WeeklyPlanService.php`
1. **`getTeacherCountryWeeklySummary()`**: 
   - Thay thế MySQL `WEEK(date, 3)` (ISO mode) bằng logic tính toán thủ công
   - Tính ngày thứ 2 đầu tiên của năm (`$firstMondayStr`)
   - Tuần = `FLOOR(DATEDIFF(date, firstMonday) / 7) + 1`
   - Ngày trước thứ 2 đầu tiên = Tuần 0

2. **`getWeeksInRange()`**:
   - Áp dụng cùng logic tính tuần cho Plan sheet
   - `$firstMondayOfYear` được tính dựa trên `dayOfWeekIso`

## Kết quả
- Tuần 1/2026: 02/01/2026 - 08/01/2026
- Tuần 2/2026: 09/01/2026 - 15/01/2026
- ...

## Status: ✅ Complete

