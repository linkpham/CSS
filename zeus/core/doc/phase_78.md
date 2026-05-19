# Phase 78 - Fix Scheduled Sessions Table & Export Query

## Yêu cầu
Kiểm tra đảm bảo không có sự sai sót trong bảng dữ liệu `🌍 Tổng số ca Scheduled` và tính năng xuất dữ liệu trên trang `/daily-ops?program=speakwell`.

## Phát hiện lỗi

### Bảng "🌍 Tổng số ca Scheduled" trên UI
- ✅ Query trong `getTeacherCountryWeeklyWithClassSize()` đã đúng với SQL yêu cầu
- ✅ Sử dụng `TEACHER_COUNTRY_SUBJECT_IDS` (không có 533) - đúng
- ✅ `ordles_status IN (2,3,4)` - đúng
- ✅ `o.order_status = 2 AND o.order_payment_status = 1` - đúng

### Tính năng Xuất Excel (`generateExcel()`)
- ❌ Sử dụng `SPEAKWELL_SUBJECT_IDS` (có 533) thay vì `TEACHER_COUNTRY_SUBJECT_IDS`
- ❌ Chỉ lọc `ordles_status = 2` thay vì `IN (2,3,4)`
- ❌ Thiếu điều kiện `o.order_status = 2 AND o.order_payment_status = 1` cho scheduled query
- ❌ Unscheduled query sử dụng `o.order_status = 'active'` thay vì `o.order_status = 2`

## Sửa chữa

File: `src/app/Services/WeeklyPlanService.php`

1. Thay `SPEAKWELL_SUBJECT_IDS` → `TEACHER_COUNTRY_SUBJECT_IDS` trong `getWeekSessionStats()`
2. Thay `ordles_status = 2` → `ordles_status IN (2,3,4)` 
3. Thêm `o.order_status = 2 AND o.order_payment_status = 1` cho scheduled query
4. Thay `o.order_status = 'active'` → `o.order_status = 2 AND o.order_payment_status = 1` cho unscheduled query

## SQL Tham chiếu (đã đúng)
```sql
SELECT ... FROM tbl_order_lessons ordles
INNER JOIN tbl_orders o ON o.order_id = ordles.ordles_order_id 
WHERE ordles.ordles_status IN (2,3,4)
  AND ordles_tlang_id IN (558, 560, 562, 580, 581, 564, 567, 568, 569, 416, 415, 414, 413, 571, 572, 574, 575, 576, 389, 390, 392, 405, 406, 407, 411, 412, 577, 586, 585, 584, 582, 404, 403, 583, 471) 
  AND o.order_status = 2 
  AND o.order_payment_status = 1
```