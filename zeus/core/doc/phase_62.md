# Phase 62 - Thêm bảng Tổng số ca theo Quốc gia GV

## Yêu cầu
- Tiếp tục chỉnh sửa từ phase 61, thêm báo cáo tổng số ca (scheduled) theo quốc gia giáo viên từng tuần
- Hiển thị dưới dạng bảng trong trang "Vận hành" (/daily-ops?program=speakwell)
- Vị trí: dưới khu vực "⏰ Trạng thái Ca học Thời gian thực"

## Thay đổi
### 1. WeeklyPlanService.php
- Thêm method `getTeacherCountryWeeklySummary()` sử dụng SQL query giống định dạng Plan.xlsx
- Query sessions với status=2 (Scheduled) từ NOW() đến 2027-01-01
- Group by teacher country và week (YEARWEEK format)
- Trả về: weeks[], countries[], generated_at

### 2. DashboardController.php
- Thêm method `apiTeacherCountryWeekly()` để expose API endpoint

### 3. routes/web.php
- Thêm route: GET `/api/teacher-country-weekly`

### 4. daily-ops.blade.php
- Thêm block "🌍 Tổng số ca theo Quốc gia GV (Tuần)" ngay sau Yesterday heatmap
- Bảng hiển thị: Countries (rows) x Weeks (columns) với tổng cộng
- Dòng đầu tiên: TỔNG CỘNG (highlighted màu xanh)
- Cột cuối: TỔNG theo country
- JavaScript function `teacherCountryWeekly()` để fetch và render data

## SQL Query sử dụng
```sql
SELECT
    base.teacher_country_id,
    base.teacher_country_name,
    YEARWEEK(base.starttime_utc7, 3) AS year_week,
    YEAR(base.starttime_utc7) AS year,
    WEEK(base.starttime_utc7, 3) AS week_of_year,
    DATE_FORMAT(...) AS week_start_ddmmYYYY,
    DATE_FORMAT(...) AS week_end_ddmmYYYY,
    COUNT(*) AS lesson_count
FROM (
    SELECT teacher.user_country_id, country_name, CONVERT_TZ(starttime, '+00:00', '+07:00')
    FROM tbl_order_lessons ordles
    INNER JOIN tbl_users teacher ON teacher.user_id = ordles.ordles_teacher_id
    LEFT JOIN tbl_countries c ON c.country_id = teacher.user_country_id
    LEFT JOIN tbl_countries_lang cl ON cl.countrylang_country_id = c.country_id AND cl.countrylang_lang_id = 1
    WHERE ordles.ordles_status = 2
      AND ordles.ordles_lesson_starttime >= NOW()
      AND ordles.ordles_lesson_starttime < '2027-01-01'
      AND ordles.ordles_tlang_id IN (SPW Subject IDs)
) AS base
GROUP BY country, year_week
ORDER BY year_week, country_name
```

## Status: ✅ Completed
