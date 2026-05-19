# Phase 66 - Tiếp tục chỉnh sửa kết quả từ chức năng "Xuất Plan" ✅

## Status: COMPLETED

### Implemented:
1. ✅ Updated getTeacherCountryWeeklySummary query with paid order conditions
2. ✅ Added unscheduled summary to Excel export (Teacher Country sheet)
3. ✅ Added UI block "📅 Tổng số ca Unscheduled theo Quốc gia GV" below weekly block
4. ✅ Added API endpoint `/api/teacher-country-unscheduled`

---

- Hãy thay đổi truy vấn cho kết quả file trích xuất từ "Xuất Plan" và phần `🌍 Tổng số ca theo Quốc gia GV (Tuần)` với query sau (thêm điều kiện đơn hàng đã thanh toán + môn học thuộc speakwell):
```
SELECT
    base.teacher_country_id,
    base.teacher_country_name,
    YEARWEEK(base.starttime_utc7, 3)   AS year_week,
    YEAR(base.starttime_utc7)           AS year,
    WEEK(base.starttime_utc7, 3)       AS week_of_year,
    DATE_FORMAT(
        MIN(DATE(base.starttime_utc7)) - INTERVAL WEEKDAY(MIN(DATE(base.starttime_utc7))) DAY,
        '%d/%m/%Y'
    ) AS week_start_ddmmYYYY,
    DATE_FORMAT(
        MIN(DATE(base.starttime_utc7)) - INTERVAL WEEKDAY(MIN(DATE(base.starttime_utc7))) DAY + INTERVAL 6 DAY,
        '%d/%m/%Y'
    ) AS week_end_ddmmYYYY,
    COUNT(*) AS lesson_count
FROM (
    SELECT
        teacher.user_country_id     AS teacher_country_id,
        IFNULL(cl.country_name, c.country_identifier) AS teacher_country_name,
        CONVERT_TZ(ordles.ordles_lesson_starttime, '+00:00', '+07:00') AS starttime_utc7
    FROM tbl_order_lessons ordles
	INNER JOIN tbl_orders o ON o.order_id = ordles.ordles_order_id 
    INNER JOIN tbl_users teacher
        ON teacher.user_id = ordles.ordles_teacher_id
    LEFT JOIN tbl_countries c
        ON c.country_id = teacher.user_country_id
    LEFT JOIN tbl_countries_lang cl
        ON cl.countrylang_country_id = c.country_id
        AND cl.countrylang_lang_id = 1
    WHERE ordles.ordles_status = 2
      AND ordles.ordles_lesson_starttime >= NOW()
      AND ordles.ordles_lesson_starttime < '2027-01-01'
 AND ordles_tlang_id IN (558, 560, 562, 580, 581, 564, 567, 568, 569, 416, 415, 414, 413, 571, 572, 574, 575, 576, 389, 390, 392, 405, 406, 407, 411, 412, 577, 586, 585, 584, 582, 404, 403, 583, 471) 
 AND o.order_status = 2 
 AND o.order_payment_status = 1
) AS base
GROUP BY
    base.teacher_country_id,
    base.teacher_country_name,
    YEARWEEK(base.starttime_utc7, 3),
    YEAR(base.starttime_utc7),
    WEEK(base.starttime_utc7, 3)
ORDER BY year_week, base.teacher_country_name;
```
- Nút "Xuất Plan" cần xuất thêm sheet với format tương tự nhưng dành cho các ca học unscheduled với query sau:
```
SELECT teacher.user_country_id     AS teacher_country_id,
    IFNULL(cl.country_name, c.country_identifier) AS teacher_country_name,
	count(ordles_id) as unscheduled_count
FROM `tbl_order_lessons` ol 
	INNER JOIN tbl_orders o ON o.order_id = ol.ordles_order_id 
	INNER JOIN tbl_users teacher
        ON teacher.user_id = ol.ordles_teacher_id
    LEFT JOIN tbl_countries c
        ON c.country_id = teacher.user_country_id
    LEFT JOIN tbl_countries_lang cl
        ON cl.countrylang_country_id = c.country_id
        AND cl.countrylang_lang_id = 1
 
WHERE 
	`ordles_status` = 1 
	and ordles_tlang_id IN (558, 560, 562, 580, 581, 564, 567, 568, 569, 416, 415, 414, 413, 571, 572, 574, 575, 576, 389, 390, 392, 405, 406, 407, 411, 412, 577, 586, 585, 584, 582, 404, 403, 583, 471) 
	and o.order_status = 2 
	and o.order_payment_status = 1
GROUP BY teacher.user_country_id;
```
Bổ sung thêm block dưới `🌍 Tổng số ca theo Quốc gia GV (Tuần)` block dành cho các ca Unscheduled