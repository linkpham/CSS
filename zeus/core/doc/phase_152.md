# Phase 152

- Trong trang `Chăm sóc CSI`, cần bổ sung các chỉ số về 3 buổi học đầu tiên gồm: Ngày xếp lịch đầu tiên, trạng thái buổi học đầu tiên (thành công, noshow, 1/2, GV noshow...), ngày học thứ 2 -trạng thái buổi học thứ 2, ngày học thứ 3 - trạng thái của buổi học thứ 3, tỉ lệ học thành công của 3 buổi học đầu tiên.... và các chỉ số này vẫn phải được query theo các bộ lọc đã thiết lập. 

```
WITH extras_one_row AS (
    -- Mỗi lesson lấy 1 row extras (ole_id nhỏ nhất) để tránh nhân bản dữ liệu
    SELECT
        e.ole_ordles_id,
        e.ole_acceptance_code,
        ROW_NUMBER() OVER (
            PARTITION BY e.ole_ordles_id
            ORDER BY e.ole_id ASC
        ) AS rn
    FROM tbl_order_lessons_extras e
),
ranked_lessons AS (
    SELECT
        ol.ordles_beneficiary_id,
        ol.ordles_id,
        ol.ordles_lesson_starttime,
        COALESCE(ex.ole_acceptance_code, 0) AS ole_acceptance_code,
        ROW_NUMBER() OVER (
            PARTITION BY ol.ordles_beneficiary_id
            ORDER BY ol.ordles_lesson_starttime ASC, ol.ordles_id ASC
        ) AS lesson_no
    FROM tbl_order_lessons ol
    LEFT JOIN extras_one_row ex
        ON ex.ole_ordles_id = ol.ordles_id
       AND ex.rn = 1
    WHERE ol.ordles_status = 3
      AND ol.ordles_beneficiary_id IS NOT NULL
    AND ol.ordles_lesson_starttime >= '2025-11-04'
),
first_3 AS (
    SELECT *
    FROM ranked_lessons
    WHERE lesson_no <= 3
)
SELECT
    f.ordles_beneficiary_id,

    MAX(CASE WHEN f.lesson_no = 1 THEN f.ordles_id END)               AS lesson_1_id,
    MAX(CASE WHEN f.lesson_no = 1 THEN f.ordles_lesson_starttime END) AS lesson_1_starttime,
    MAX(CASE WHEN f.lesson_no = 1 THEN f.ole_acceptance_code END)     AS lesson_1_acceptance_code,

    MAX(CASE WHEN f.lesson_no = 2 THEN f.ordles_id END)               AS lesson_2_id,
    MAX(CASE WHEN f.lesson_no = 2 THEN f.ordles_lesson_starttime END) AS lesson_2_starttime,
    MAX(CASE WHEN f.lesson_no = 2 THEN f.ole_acceptance_code END)     AS lesson_2_acceptance_code,

    MAX(CASE WHEN f.lesson_no = 3 THEN f.ordles_id END)               AS lesson_3_id,
    MAX(CASE WHEN f.lesson_no = 3 THEN f.ordles_lesson_starttime END) AS lesson_3_starttime,
    MAX(CASE WHEN f.lesson_no = 3 THEN f.ole_acceptance_code END)     AS lesson_3_acceptance_code,

    SUM(CASE WHEN f.ole_acceptance_code IN (9, 12) THEN 1 ELSE 0 END) AS success_lessons_3,
    ROUND(
        SUM(CASE WHEN f.ole_acceptance_code IN (9, 12) THEN 1 ELSE 0 END) / 3 * 100,
        2
    ) AS success_rate_percent

FROM first_3 f
GROUP BY f.ordles_beneficiary_id
ORDER BY f.ordles_beneficiary_id;
```