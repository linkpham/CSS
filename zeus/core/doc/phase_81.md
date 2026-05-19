# Phase 81 - Fix Weekly Unscheduled Breakdown Accuracy

## Vấn đề
Kết quả Lọc số ca Unscheduled không khớp với bảng `🌍 Tổng số ca Scheduled` do:
1. Sử dụng WEEK mode khác nhau (mode 1 vs mode 3 ISO-8601)
2. Không convert timezone UTC+7
3. Chỉ lấy `ordles_status = 2` thay vì `IN (2, 3, 4)` như bảng Scheduled

## Giải pháp
Cập nhật `getWeeklyUnscheduledBreakdown()` để sử dụng cùng logic với `getTeacherCountryWeeklyWithClassSize()`:
- WEEK mode 3 (ISO-8601) 
- CONVERT_TZ để chuyển về UTC+7
- `ordles_status IN (2, 3, 4)` để khớp với bảng Scheduled

## Thay đổi

### `WeeklyPlanService.php::getWeeklyUnscheduledBreakdown()`
```sql
-- Before (sai)
YEARWEEK(ol.ordles_lesson_starttime, 1) AS year_week
WHERE ol.ordles_status = 2

-- After (đúng) 
YEARWEEK(CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00'), 3) AS year_week
WHERE ol.ordles_status IN (2, 3, 4)
```

## Hoàn thành
- [x] Fix WEEK mode 1 → mode 3 (ISO-8601)
- [x] Add CONVERT_TZ for UTC+7
- [x] Fix ordles_status từ = 2 sang IN (2, 3, 4)