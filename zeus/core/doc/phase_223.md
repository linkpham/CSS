# Phase 223 ✅

- Trong trang `Vận hành Hôm nay` (/daily-ops?program=all), block `🚫 Tình trạng hủy Ca học` cần phân loại cho lớp 1:1 và lớp 1:2. Hãy xem query sau để chỉnh sửa:
```
Câu lệnh cho lớp 1-1
SELECT sl.sesslog_user_type, COUNT(DISTINCT ol.ordles_id) FROM tbl_order_lessons ol INNER JOIN tbl_session_logs sl ON ol.ordles_id = sl.sesslog_record_id AND sl.sesslog_record_type=1 WHERE ol.ordles_status = 4 AND sl.sesslog_changed_status = 4 AND ol.ordles_tlang_id IN (533,389,390,392,403,404,405,406,407,411,412,413,414,415,416,471,558,560,562,564,567,568,569,571,572,574,575,576,577,580,581,582,583,584,585,586) AND ol.ordles_updated > DATE_SUB(ol.ordles_lesson_starttime, INTERVAL 1 DAY) AND ol.ordles_lesson_starttime BETWEEN '2026-05-05 17:00:00' AND '2026-05-06 17:00:00' GROUP BY sl.sesslog_user_type;


Câu lệnh cho lớp 1-2
SELECT sl.sesslog_user_type, COUNT(DISTINCT gc.grpcls_id) FROM tbl_group_classes gc INNER JOIN tbl_session_logs sl ON gc.grpcls_id = sl.sesslog_record_id AND sl.sesslog_record_type=2 WHERE gc.grpcls_status = 3 AND sl.sesslog_changed_status = 3 AND gc.grpcls_tlang_id IN (533,389,390,392,403,404,405,406,407,411,412,413,414,415,416,471,558,560,562,564,567,568,569,571,572,574,575,576,577,580,581,582,583,584,585,586) AND sl.sesslog_created > DATE_SUB(gc.grpcls_start_datetime, INTERVAL 1 DAY) AND gc.grpcls_start_datetime BETWEEN '2026-05-05 17:00:00' AND '2026-05-06 17:00:00' GROUP BY sl.sesslog_user_type;
```

Lưu ý, hãy cung cấp chi tiết và đầy đủ câu lệnh SQL tại nút `ⓘ`.

## Thực hiện

### 1. SessionLog Model
- Thêm constant `RECORD_TYPE_GROUP_CLASS = 2` cho group class record type

### 2. Backend (DashboardService.php)
- Tách `getCancellationStats()` thành 2 loại: 1:1 (tbl_order_lessons) và 1:2 (tbl_group_classes)
- **1:1**: giữ nguyên logic cũ (sesslog_record_type=1, ordles_status=4, sesslog_changed_status=4)
- **1:2**: thêm query mới (sesslog_record_type=2, grpcls_status=3, sesslog_changed_status=3)
  - Time condition: `sesslog_created > DATE_SUB(grpcls_start_datetime, INTERVAL 1 DAY)` (khác với 1:1 dùng `ordles_updated`)
  - Student info: subquery GROUP_CONCAT từ tbl_order_classes + tbl_users
  - Search: hỗ trợ tìm kiếm GV + HV qua EXISTS subquery
- Trả về data mới: `one_on_one` + `one_on_two` sub-arrays trong response
- Tổng hợp combined totals (total, by_student, by_teacher, by_admin)
- Detail list: merge 2 nguồn, sort theo priority (GV > HS > Admin), paginate trong PHP
- Thêm helper methods: `getCancellation1v1Details()` và `getCancellation1v2Details()`

### 3. Frontend (daily-ops-program-content.blade.php)
- Giữ nguyên summary cards tổng (Tổng hủy, HS, GV, Admin)
- Thêm 2 block phân loại: "👤 Lớp 1:1" và "👥 Lớp 1:2" với breakdown riêng
- Thêm cột "Loại" (class_type) vào bảng chi tiết, hiển thị badge 1:1 / 1:2
- Cập nhật SQL tooltip (ⓘ) với đầy đủ 2 câu lệnh SQL cho 1:1 và 1:2

### 4. Frontend (daily-ops-page-script.blade.php)
- Thêm cột "Loại lớp" vào export Excel CSV
- Thêm `program` param vào `_fetchDetails()` và export API calls

### Files changed
- `src/app/Models/SessionLog.php` – thêm RECORD_TYPE_GROUP_CLASS = 2
- `src/app/Services/DashboardService.php` – tách query 1:1/1:2, thêm helper methods
- `src/resources/views/dashboard/partials/daily-ops-program-content.blade.php` – UI phân loại + SQL tooltip
- `src/resources/views/dashboard/partials/daily-ops-page-script.blade.php` – export + program param
