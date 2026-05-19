# Phase 227 ✅
- Tại trang `Vận hành` (/daily-ops?program=all), block `📅 Tổng số ca Unscheduled`, khi lọc `📊 Ca Unscheduled theo tuần` cần bổ sung phân loại cho lớp 1:1 và lớp 1:2.

## Thực hiện

### 1. Backend (WeeklyPlanService.php)
- Sửa `getWeeklyUnscheduledBreakdown()` bổ sung:
  - Query đếm HV active theo class type (1:1 vs 1:2) sử dụng LEFT JOIN `tbl_group_classes`
  - Query đếm scheduled sessions theo tuần VÀ class type
  - Phân loại: `grpcls_total_seats >= 2` → 1:2, còn lại → 1:1
- Trả về thêm per-week: `one_on_one_unscheduled`, `one_on_two_unscheduled`, `one_on_one_scheduled`, `one_on_two_scheduled`
- Trả về thêm totals: `one_on_one_total_unscheduled`, `one_on_two_total_unscheduled`, `one_on_one_active_students`, `one_on_two_active_students`

### 2. Frontend (daily-ops-program-content.blade.php)
- Thêm 2 cột "👤 1:1" và "👥 1:2" vào bảng "📊 Ca Unscheduled theo tuần"
- Hiển thị trong cả dòng TỔNG CỘNG và từng dòng tuần
- Cập nhật SQL tooltip với query phân loại class_type

### SQL Query (Scheduled/tuần + class_type)
```sql
SELECT 
    YEARWEEK(CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00'), 3) AS year_week,
    CASE WHEN gc.grpcls_total_seats >= 2 THEN '1:2' ELSE '1:1' END AS class_type,
    COUNT(*) AS scheduled_count
FROM tbl_order_lessons ol
INNER JOIN tbl_orders o ON o.order_id = ol.ordles_order_id
LEFT JOIN (
    SELECT grpcls_tlang_id, grpcls_teacher_id, MAX(grpcls_total_seats) as grpcls_total_seats
    FROM tbl_group_classes GROUP BY grpcls_tlang_id, grpcls_teacher_id
) gc ON gc.grpcls_tlang_id = ol.ordles_tlang_id AND gc.grpcls_teacher_id = ol.ordles_teacher_id
WHERE ol.ordles_status IN (2, 3, 4)
  AND ol.ordles_tlang_id IN (...)
  AND o.order_status = 2 AND o.order_payment_status = 1
  AND CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00') BETWEEN ? AND ?
GROUP BY year_week, class_type
```

### Files changed
- `src/app/Services/WeeklyPlanService.php` – thêm query class type + tính toán breakdown
- `src/resources/views/dashboard/partials/daily-ops-program-content.blade.php` – thêm cột 1:1 / 1:2 vào bảng tuần
