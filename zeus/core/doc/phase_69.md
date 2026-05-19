# Phase 69 - Tiếp tục sửa lỗi phase 66,67,68

- Thay lại query của sheet Plan và `🌍 Tổng số ca theo Quốc gia GV (Tuần)` theo query mới sau:
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
    WHERE ordles.ordles_status IN (2,3,4)
      AND ordles.ordles_lesson_starttime >= '2026-01-01'
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
ORDER BY year_week, base.teacher_country_name;```

Tuy nhiên hãy thay nội dung query trên với năm trong `'2026-01-01' bằn năm hiện tại và năm trong `'2027-01-01'` bằng năm kế tiếp
Hãy kiểm tra lại thật kỹ lưỡng.