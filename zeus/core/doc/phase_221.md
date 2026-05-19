# Phase 221 ✅

- Trong trang `Chăm sóc CSI`, hãy rà soát và cập nhật lại các chỉ số của SPW dựa trên các query sau:
```
1. Chăm sóc CSI -> Tổng học viên SPEAKWELL

SELECT COUNT(DISTINCT t.student_id) FROM ( SELECT DISTINCT ol.ordles_beneficiary_id as student_id FROM tbl_order_lessons AS ol WHERE ol.ordles_tlang_id IN (389,390,392,403,404,405,406,407,411,412,413,414,415,416,471,558,560,562,564,567,568,569,571,572,574,575,576,577,580,581,582,583,584,585,586) UNION ALL SELECT DISTINCT oc.ordcls_beneficiary_id as student_id FROM tbl_order_classes oc INNER JOIN tbl_group_classes gce ON oc.ordcls_grpcls_id = gce.grpcls_id WHERE gce.grpcls_tlang_id IN (389,390,392,403,404,405,406,407,411,412,413,414,415,416,471,558,560,562,564,567,568,569,571,572,574,575,576,577,580,581,582,583,584,585,586)) t;


2. Chăm sóc CSI -> Học viên SPW Active

SELECT COUNT(DISTINCT t.student_id) FROM
(
SELECT DISTINCT u.user_id as student_id
FROM tbl_users AS u
INNER JOIN tbl_orders AS o
    ON o.order_user_id = u.user_id
INNER JOIN tbl_order_lessons AS ol
    ON ol.ordles_order_id = o.order_id
WHERE u.user_lastseen >= NOW() - INTERVAL 30 DAY
  AND ol.ordles_status = 3
  AND ol.ordles_tlang_id IN (389,390,392,403,404,405,406,407,411,412,413,414,415,416,471,558,560,562,564,567,568,569,571,572,574,575,576,577,580,581,582,583,584,585,586)
  AND ol.ordles_lesson_endtime >= NOW() - INTERVAL 30 DAY
  AND ol.ordles_lesson_endtime <= NOW()
UNION ALL
SELECT DISTINCT oc.ordcls_beneficiary_id as student_id 
FROM tbl_order_classes oc 
INNER JOIN tbl_group_classes gce ON oc.ordcls_grpcls_id = gce.grpcls_id 
INNER JOIN tbl_users u ON oc.ordcls_beneficiary_id = u.user_id
WHERE 
gce.grpcls_tlang_id IN (389,390,392,403,404,405,406,407,411,412,413,414,415,416,471,558,560,562,564,567,568,569,571,572,574,575,576,577,580,581,582,583,584,585,586) 
AND gce.grpcls_status = 2
AND u.user_lastseen >= NOW() - INTERVAL 30 DAY
) t;

3. Chăm sóc CSI -> User inactive (danh sách user)
WITH lang_ids AS (
    SELECT 389 AS id UNION ALL SELECT 390 UNION ALL SELECT 392 UNION ALL
    SELECT 403 UNION ALL SELECT 404 UNION ALL SELECT 405 UNION ALL SELECT 406 UNION ALL
    SELECT 407 UNION ALL SELECT 411 UNION ALL SELECT 412 UNION ALL SELECT 413 UNION ALL
    SELECT 414 UNION ALL SELECT 415 UNION ALL SELECT 416 UNION ALL SELECT 471 UNION ALL
    SELECT 558 UNION ALL SELECT 560 UNION ALL SELECT 562 UNION ALL SELECT 564 UNION ALL
    SELECT 567 UNION ALL SELECT 568 UNION ALL SELECT 569 UNION ALL SELECT 571 UNION ALL
    SELECT 572 UNION ALL SELECT 574 UNION ALL SELECT 575 UNION ALL SELECT 576 UNION ALL
    SELECT 577 UNION ALL SELECT 580 UNION ALL SELECT 581 UNION ALL SELECT 582 UNION ALL
    SELECT 583 UNION ALL SELECT 584 UNION ALL SELECT 585 UNION ALL SELECT 586
),
q1 AS (
    -- Tập 1: tất cả student_id theo điều kiện ngôn ngữ
    SELECT ol.ordles_beneficiary_id AS student_id
    FROM tbl_order_lessons ol
    JOIN lang_ids l ON l.id = ol.ordles_tlang_id

    UNION

    SELECT oc.ordcls_beneficiary_id AS student_id
    FROM tbl_order_classes oc
    JOIN tbl_group_classes gce ON gce.grpcls_id = oc.ordcls_grpcls_id
    JOIN lang_ids l ON l.id = gce.grpcls_tlang_id
),
q2 AS (
    -- Tập 2: active trong 30 ngày gần nhất (lesson/class)
    SELECT u.user_id AS student_id
    FROM tbl_users u
    JOIN tbl_orders o ON o.order_user_id = u.user_id
    JOIN tbl_order_lessons ol ON ol.ordles_order_id = o.order_id
    JOIN lang_ids l ON l.id = ol.ordles_tlang_id
    WHERE u.user_lastseen >= NOW() - INTERVAL 30 DAY
      AND ol.ordles_status = 3
      AND ol.ordles_lesson_endtime BETWEEN NOW() - INTERVAL 30 DAY AND NOW()

    UNION

    SELECT oc.ordcls_beneficiary_id AS student_id
    FROM tbl_order_classes oc
    JOIN tbl_group_classes gce ON gce.grpcls_id = oc.ordcls_grpcls_id
    JOIN tbl_users u ON u.user_id = oc.ordcls_beneficiary_id
    JOIN lang_ids l ON l.id = gce.grpcls_tlang_id
    WHERE gce.grpcls_status = 2
      AND u.user_lastseen >= NOW() - INTERVAL 30 DAY
)
SELECT
    u.user_id,
    u.user_username,
    u.user_last_name,
    u.user_first_name,
    u.user_email
FROM q1
JOIN tbl_users u ON u.user_id = q1.student_id
WHERE NOT EXISTS (
    SELECT 1
    FROM q2
    WHERE q2.student_id = q1.student_id
)
ORDER BY u.user_id;

Nếu chỉ lấy số đếm:

WITH q1 AS (
    SELECT ol.ordles_beneficiary_id AS student_id
    FROM tbl_order_lessons ol
    WHERE ol.ordles_tlang_id IN (389,390,392,403,404,405,406,407,411,412,413,414,415,416,471,558,560,562,564,567,568,569,571,572,574,575,576,577,580,581,582,583,584,585,586)

    UNION

    SELECT oc.ordcls_beneficiary_id AS student_id
    FROM tbl_order_classes oc
    JOIN tbl_group_classes gce ON gce.grpcls_id = oc.ordcls_grpcls_id
    WHERE gce.grpcls_tlang_id IN (389,390,392,403,404,405,406,407,411,412,413,414,415,416,471,558,560,562,564,567,568,569,571,572,574,575,576,577,580,581,582,583,584,585,586)
),
q2 AS (
    SELECT u.user_id AS student_id
    FROM tbl_users u
    JOIN tbl_orders o ON o.order_user_id = u.user_id
    JOIN tbl_order_lessons ol ON ol.ordles_order_id = o.order_id
    WHERE u.user_lastseen >= NOW() - INTERVAL 30 DAY
      AND ol.ordles_status = 3
      AND ol.ordles_tlang_id IN (389,390,392,403,404,405,406,407,411,412,413,414,415,416,471,558,560,562,564,567,568,569,571,572,574,575,576,577,580,581,582,583,584,585,586)
      AND ol.ordles_lesson_endtime BETWEEN NOW() - INTERVAL 30 DAY AND NOW()

    UNION

    SELECT oc.ordcls_beneficiary_id AS student_id
    FROM tbl_order_classes oc
    JOIN tbl_group_classes gce ON gce.grpcls_id = oc.ordcls_grpcls_id
    JOIN tbl_users u ON u.user_id = oc.ordcls_beneficiary_id
    WHERE gce.grpcls_tlang_id IN (389,390,392,403,404,405,406,407,411,412,413,414,415,416,471,558,560,562,564,567,568,569,571,572,574,575,576,577,580,581,582,583,584,585,586)
      AND gce.grpcls_status = 2
      AND u.user_lastseen >= NOW() - INTERVAL 30 DAY
)
SELECT COUNT(DISTINCT q1.student_id) AS total_inactive_users
FROM q1
WHERE NOT EXISTS (
    SELECT 1
    FROM q2
    WHERE q2.student_id = q1.student_id
);

```


- Trong trang `Chăm sóc CSI`, mục `💤 HV Inactive SpeakWell`, tôi cần thêm 1 nút mở dialog xem chi tiết danh sách các học viên inactive và có thêm tổng số những học viên có tổng số buổi unschedule và schedule = 0 (unschedule + schedule = 0). Danh sách có thể export ra excel được.

## Thực hiện

### Backend (CsiService.php)
- Thêm constant `SPW_TLANG_IDS` dùng chung cho tất cả query SPW
- Cập nhật `getSpeakwellStudentStats()`: thêm `tbl_order_classes` + `tbl_group_classes` vào cả 3 query (Total, Active, Inactive) qua UNION ALL
- Inactive count dùng WITH CTE + NOT EXISTS thay vì Total - Active (chính xác hơn)
- Thêm method `getInactiveStudentsList()`: trả về danh sách HV inactive phân trang, kèm:
  - `unscheduled_count` (ordles_status = 1) và `scheduled_count` (ordles_status = 2) cho mỗi HV
  - `zero_lessons_count`: tổng số HV có 0 buổi còn lại (unschedule + schedule = 0)
  - Hỗ trợ search theo ID / tên / email / username

### Backend (CsiController.php)
- Thêm method `apiSpwInactive()` xử lý request phân trang + search

### Routes (web.php)
- Thêm route `GET /api/csi/spw-inactive`

### Frontend (csi/index.blade.php)
- Cập nhật SQL tooltip cho 3 card SPW (Total, Active, Inactive) phản ánh query mới có tbl_order_classes
- Thêm nút "📋 Xem chi tiết" trong card 💤 HV Inactive SpeakWell
- Thêm dialog modal hiển thị:
  - Header: tổng HV inactive + số HV có 0 buổi còn lại
  - Ô search + nút lọc
  - Bảng: ID, Username, Tên HV, Email, Chưa lên lịch, Đã lên lịch, Tổng còn lại
  - Phân trang
  - Nút "📥 Xuất Excel" export toàn bộ danh sách ra CSV (kèm dòng tổng kết)