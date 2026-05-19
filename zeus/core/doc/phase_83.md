# Phase 83 - Tiếp tục thực hiện yêu cầu mới

## Yêu cầu
- Dữ liệu lọc `📊 Ca Unscheduled theo tuần` được tính từ tuần hiện tại trở đi, cần đảm bảo dữ liệu là của học sinh sản phẩm SPW (sử dụng điều kiện `AND ordles_tlang_id IN (558, 560, 562, 580, 581, 564, 567, 568, 569, 416, 415, 414, 413, 571, 572, 574, 575, 576, 389, 390, 392, 405, 406, 407, 411, 412, 577, 586, 585, 584, 582, 404, 403, 583, 471) trong SQL`
- Phải cập nhật giải thích và cung cấp SQL vào ⓘ trong block `📊 Ca Unscheduled theo tuần`

## Thực hiện

### Xác minh logic backend
- Kiểm tra `WeeklyPlanService::getWeeklyUnscheduledBreakdown()` - đã có filter SPW subject IDs
- Constant `TEACHER_COUNTRY_SUBJECT_IDS` đã chứa đúng danh sách subject IDs
- Cả 2 query (HV active và Scheduled/tuần) đều đã áp dụng filter này

### Cập nhật tooltip
- File: `src/resources/views/dashboard/daily-ops.blade.php`
- Thêm giải thích "Chỉ tính học sinh sản phẩm SPW (ordles_tlang_id IN danh sách)"
- Cập nhật SQL queries trong tooltip để hiển thị đầy đủ điều kiện SPW

## Hoàn thành
✅ Tooltip đã được cập nhật với đầy đủ SQL và giải thích filter SPW
