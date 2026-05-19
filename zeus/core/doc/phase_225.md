# Phase 225
- Tại trang KPI ('Tổng quan Hệ thống'), tại block `📊 Thống kê Ca học`, hãy bổ sung các chỉ số theo phân loại cho lớp 1:1 và lớp 1:2. Lưu ý, hãy cung cấp chi tiết và đầy đủ câu lệnh SQL tại nút `ⓘ`

## Thực hiện

### Backend (DashboardService.php)
- Thêm method `getClassTypeBreakdown()` truy vấn `tbl_group_classes` cho số liệu lớp 1:2
- Bổ sung key `class_type_breakdown` vào kết quả `getSessionSuccessFailureBreakdown()`
- Lớp 1:1: tái sử dụng dữ liệu đã tính từ `tbl_order_lessons`
- Lớp 1:2: truy vấn `tbl_group_classes` cho: tổng, đã lên lịch, hoàn thành, đã hủy, hủy gấp (phân loại GV/HS/Admin)

### Frontend
- `session-stats-display.blade.php`: Thêm section "Chi tiết theo Loại lớp" với 2 cột Lớp 1:1 và Lớp 1:2
- `index-program-content.blade.php`: Thêm section tương ứng cho custom date (AJAX)
- Đầy đủ SQL tooltip (ⓘ) cho từng chỉ số

### SQL Queries

#### Lớp 1:1 (tbl_order_lessons)
```sql
-- Tổng ca học
SELECT COUNT(*) FROM tbl_order_lessons
WHERE ordles_tlang_id IN (...) AND ordles_status IN (2, 3, 4)
AND ordles_lesson_starttime BETWEEN [start] AND [end]

-- Đã hoàn thành / Đã lên lịch / Đã hủy
SELECT COUNT(*) FROM tbl_order_lessons
WHERE ordles_status = {2|3|4} AND ...

-- Đã tính phí
SELECT COUNT(*) FROM tbl_order_lessons ol
JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id
WHERE ol.ordles_status = 3 AND ole.ole_acceptance_code IN (4,5,6,7,8,9,10,11,12,16,17)

-- Bù buổi
SELECT COUNT(*) ... AND ole.ole_acceptance_code IN (1,2,3,13,14,15)

-- Hủy gấp
SELECT sl.sesslog_user_type, COUNT(DISTINCT ol.ordles_id)
FROM tbl_order_lessons ol
INNER JOIN tbl_session_logs sl ON ol.ordles_id = sl.sesslog_record_id AND sl.sesslog_record_type = 1
WHERE ol.ordles_status = 4 AND sl.sesslog_changed_status = 4
AND ol.ordles_updated > DATE_SUB(ol.ordles_lesson_starttime, INTERVAL 1 DAY)
GROUP BY sl.sesslog_user_type
```

#### Lớp 1:2 (tbl_group_classes)
```sql
-- Tổng ca học
SELECT COUNT(*) FROM tbl_group_classes
WHERE grpcls_tlang_id IN (...) AND grpcls_status IN (1, 2, 3)
AND grpcls_start_datetime BETWEEN [start] AND [end]

-- Đã hoàn thành (status=2) / Đã lên lịch (status=1) / Đã hủy (status=3)
SELECT grpcls_status, COUNT(*) FROM tbl_group_classes
WHERE grpcls_status IN (1, 2, 3) AND ...
GROUP BY grpcls_status

-- Hủy gấp
SELECT sl.sesslog_user_type, COUNT(DISTINCT gc.grpcls_id)
FROM tbl_group_classes gc
INNER JOIN tbl_session_logs sl ON gc.grpcls_id = sl.sesslog_record_id AND sl.sesslog_record_type = 2
WHERE gc.grpcls_status = 3 AND sl.sesslog_changed_status = 3
AND sl.sesslog_created > DATE_SUB(gc.grpcls_start_datetime, INTERVAL 1 DAY)
GROUP BY sl.sesslog_user_type
```
