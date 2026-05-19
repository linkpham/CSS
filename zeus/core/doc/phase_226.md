# Phase 226 ✅
- Tại trang `Vận hành` (/daily-ops?program=all), block `📅 Tổng số ca Unscheduled`, cần bổ sung phân loại cho lớp 1:1 và lớp 1:2.

## Thực hiện

### 1. Backend (WeeklyPlanService.php)
- Sửa `getTeacherCountryUnscheduledSummary()` bổ sung LEFT JOIN `tbl_group_classes` để xác định loại lớp
- Phân loại: `grpcls_total_seats >= 2` → 1:2, còn lại → 1:1
- Trả về thêm: `one_on_one_total`, `one_on_two_total`, và mỗi country có `one_on_one`, `one_on_two`
- Cập nhật `buildTeacherCountrySheet()` Excel export thêm cột Lớp 1:1, Lớp 1:2

### 2. Frontend (daily-ops-program-content.blade.php)
- Thêm 2 summary cards "👤 Lớp 1:1" và "👥 Lớp 1:2" với SQL tooltip chi tiết
- Bảng dữ liệu: thêm cột "👤 1:1" và "👥 1:2" bên cạnh cột TỔNG
- Cập nhật SQL tooltip chính với full query bao gồm class_type

### 3. Frontend (daily-ops-page-script.blade.php)
- Thêm `oneOnOneTotal`, `oneOnTwoTotal`, `byCountryOneOnOne`, `byCountryOneOnTwo` vào Alpine.js state
- Cập nhật `fetchData()` để parse dữ liệu mới từ API

### SQL Query
```sql
SELECT teacher.user_country_id, IFNULL(cl.country_name, c.country_identifier) AS teacher_country_name,
    CASE WHEN gc.grpcls_total_seats >= 2 THEN '1:2' ELSE '1:1' END AS class_type,
    COUNT(ol.ordles_id) AS unscheduled_count
FROM tbl_order_lessons ol
INNER JOIN tbl_orders o ON o.order_id = ol.ordles_order_id
INNER JOIN tbl_users teacher ON teacher.user_id = ol.ordles_teacher_id
LEFT JOIN tbl_countries c ON c.country_id = teacher.user_country_id
LEFT JOIN tbl_countries_lang cl ON cl.countrylang_country_id = c.country_id AND cl.countrylang_lang_id = 1
LEFT JOIN (
    SELECT grpcls_tlang_id, grpcls_teacher_id, MAX(grpcls_total_seats) as grpcls_total_seats
    FROM tbl_group_classes GROUP BY grpcls_tlang_id, grpcls_teacher_id
) gc ON gc.grpcls_tlang_id = ol.ordles_tlang_id AND gc.grpcls_teacher_id = ol.ordles_teacher_id
WHERE ol.ordles_status = 1
  AND ol.ordles_tlang_id IN (...)
  AND o.order_status = 2 AND o.order_payment_status = 1
GROUP BY teacher.user_country_id, teacher_country_name, class_type
```

### Files changed
- `src/app/Services/WeeklyPlanService.php` – sửa query + Excel export
- `src/resources/views/dashboard/partials/daily-ops-program-content.blade.php` – UI cards + table columns
- `src/resources/views/dashboard/partials/daily-ops-page-script.blade.php` – Alpine.js state + data parsing