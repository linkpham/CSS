# Phase 71 - Revert to ISO Week Numbering (Mode 3)

## Yêu cầu
- Quay lại logic ISO-8601 week (WEEK mode 3) thay vì "first Monday" rule của phase 70
- Tuần được tính từ thứ 2 đến chủ nhật
- Tuần 1 của năm 2026: 01/01/2026 (Thu) đến 04/01/2026 (Sun) - chỉ có 4 ngày
- Tuần 2 của năm 2026: 05/01/2026 (Mon) đến 11/01/2026 (Sun) - đầy đủ 7 ngày

## Thay đổi

### `src/app/Services/WeeklyPlanService.php`

1. **`getWeeksInRange()`**:
   - Xóa logic tính first Monday của năm
   - Sử dụng PHP `format('W')` để lấy ISO week number (tương đương MySQL WEEK mode 3)

2. **`getTeacherCountryWeeklySummary()`**:
   - Khôi phục query từ phase 69 với YEARWEEK(date, 3) và WEEK(date, 3)
   - Group by year_week thay vì week_of_year
   - Sử dụng năm hiện tại động (`$currentYear`, `$nextYear`)

## Kết quả (2026)
- Tuần 1: 01/01/2026 - 04/01/2026 (4 ngày)
- Tuần 2: 05/01/2026 - 11/01/2026 (7 ngày)
- ...

## Status: ✅ Complete
