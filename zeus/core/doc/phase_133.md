# Phase 133
- Dashboard của file '/Users/que/Downloads/zeus/Chăm sóc CSI.xlsx' thực chất được query như sau:
```
WITH lessons_base AS (
  SELECT
    l.ordles_id,
    l.ordles_beneficiary_id,
    l.ordles_lesson_starttime
  FROM tbl_order_lessons l
  WHERE l.ordles_beneficiary_id IS NOT NULL
    AND l.ordles_beneficiary_id > 0
    AND l.ordles_status IN (3)
  AND l.ordles_lesson_starttime > '2025-11-04'
    AND l.ordles_lesson_starttime IS NOT NULL
    AND l.ordles_lesson_starttime <= NOW()
),
extras_one AS (
  SELECT
    x.ole_ordles_id,
    x.ole_acceptance_code
  FROM (
    SELECT
      ex.*,
      ROW_NUMBER() OVER (
        PARTITION BY ex.ole_ordles_id
        ORDER BY ex.ole_id ASC
      ) AS rn
    FROM tbl_order_lessons_extras ex
  ) x
  WHERE x.rn = 1
),
joined AS (
  SELECT
    b.ordles_beneficiary_id,
    b.ordles_id,
    b.ordles_lesson_starttime,
    e.ole_acceptance_code
  FROM lessons_base b
  LEFT JOIN extras_one e
    ON e.ole_ordles_id = b.ordles_id
)
SELECT
  j.ordles_beneficiary_id as user_id,
  u.user_last_name,
  u.user_first_name,
  u.user_username,
  u.user_email,
  us.user_phone_number,
  ue.usrextra_css_id as css_id,
  a.admin_username as css_username,

  COUNT(*) AS total_lessons_happened,

  SUM(
    CASE
      WHEN j.ole_acceptance_code IN (0, 4, 7, 10) OR j.ole_acceptance_code IS NULL THEN 1
      ELSE 0
    END
  ) AS total_student_noshow,

  SUM(
    CASE
      WHEN j.ole_acceptance_code IN (2, 5, 8, 11) THEN 1
      ELSE 0
    END
  ) AS total_student_halftime,

  SUM(
    CASE
      WHEN j.ole_acceptance_code IN (3, 6, 9, 12) THEN 1
      ELSE 0
    END
  ) AS total_normal,

  SUM(
    CASE
      WHEN j.ole_acceptance_code IN (0, 2, 3) OR j.ole_acceptance_code IS NULL THEN 1
      ELSE 0
    END
  ) AS total_teacher_noshow,

  MIN(DATE(j.ordles_lesson_starttime)) AS first_lesson_date,
  DATEDIFF(
    CURDATE(),
    MIN(DATE(j.ordles_lesson_starttime))
  )/7 AS weeks_from_first_to_now,

  (COUNT(*) * 1.0) / (
    DATEDIFF(
      CURDATE(),
      MIN(DATE(j.ordles_lesson_starttime))
    ) / 7
  ) AS lessons_per_week,
  100 - SUM(
    CASE
      WHEN j.ole_acceptance_code IN (0, 4, 7, 10) OR j.ole_acceptance_code IS NULL THEN 1
      ELSE 0
    END
  ) * 10 -   SUM(
    CASE
      WHEN j.ole_acceptance_code IN (2, 5, 8, 11) THEN 1
      ELSE 0
    END
  ) * 5 as student_health_score,
  SUM(
    CASE
      WHEN j.ole_acceptance_code IN (3, 6, 9, 12) THEN 1
      ELSE 0
    END
  )*100/COUNT(*) as success_ratio,
  SUM(
    CASE
      WHEN j.ole_acceptance_code IN (0, 2, 3) OR j.ole_acceptance_code IS NULL THEN 1
      ELSE 0
    END
  )*100 / COUNT(*) as teacher_noshow_ratio

FROM joined j
INNER JOIN tbl_users u ON j.ordles_beneficiary_id = u.user_id
LEFT JOIN tbl_user_settings us ON u.user_id = us.user_id
LEFT JOIN tbl_user_extras ue ON u.user_id = ue.usrextra_user_id
LEFT JOIN tbl_admin a ON ue.usrextra_css_id = a.admin_id
GROUP BY j.ordles_beneficiary_id
ORDER BY j.ordles_beneficiary_id;
```
Hãy chỉnh sửa toàn bộ trang `Chăm sóc CSI` để phản ánh đúng dữ liệu được lấy từ các bảng biểu `tbl_users`, `tbl_user_settings`, `tbl_admin`,`tbl_order_lessons`,`tbl_order_lessons_extras`,`lessons_base`, .... Không tự bịa ra các bảng dữ liệu mà không tồn tại. 