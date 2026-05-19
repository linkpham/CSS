# Phase 63 - Tiếp tục chỉnh sửa phase 62

## Yêu cầu
- Kiểm tra kết quả file trích xuất từ "Xuất Plan":
	- Định dạng giống như sheet Plan trong `/Users/que/Downloads/Plan.xlsx`
	- Teacher nationality: Vietnamese, Philippines, Native 1 (Nam Phi), Native 2 (UK)
 	- Đảm bảo kết quả phù hợp với SQL

## Thay đổi

### Fix: Sửa mapping Teacher Nationality trong WeeklyPlanService
- **Trước**: Native 1 gộp tất cả ZA, US, GB, AU, NZ, CA
- **Sau**: 
  - Native 1 = South Africa (ZA) only
  - Native 2 = UK (GB) và các quốc gia khác

### Verified Results (Feb 2026)
| Nationality | Week 2 (02-08/02) |
|-------------|-------------------|
| Vietnamese  | 3113              |
| Philippines | 2957              |
| Native 1 (ZA) | 103             |
| Native 2 (GB) | 5               |

Matches SQL query output ✅

## SQL Reference
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