# Phase 12 ✅

## Yêu cầu
- Trong  `Hierarchical KPI Display`, nội dung `ca đang chờ ClassIn gửi data về` cần chia làm 2 nodes con:
```
1. Những ca đang chờ data của ClassIn về: Không có dữ liệu trong bảng order_lessons_extras VÀ NOW() - ordles_lesson_endtime  <= 30p 
2. Những ca KHÔNG thấy có data trên ClassIn: Không có dữ liệu trong bảng order_lessons_extras VÀ NOW() - ordles_lesson_endtime  > 30p 
```
- Hãy sửa chính tả: `ca đang chờ ClassIn gửi data về`  thành `ca chưa có dữ liệu trả về (thông thường Classin sẽ gửi data về sau mỗi 20ph)`

## Thực hiện

### 1. Backend Service (DashboardService.php)
- Cập nhật phương thức `getSessionSuccessFailureBreakdown()` để tính toán 2 loại ca mới:
  - `awaiting_within_30min`: Ca hoàn thành không có extras data VÀ kết thúc <= 30 phút trước
  - `no_data_over_30min`: Ca hoàn thành không có extras data VÀ kết thúc > 30 phút trước
- Sử dụng LEFT JOIN để tìm ca không có dữ liệu extras
- Sử dụng `TIMESTAMPDIFF(MINUTE, ordles_lesson_endtime, NOW())` để so sánh thời gian

### 2. Frontend Views
Cập nhật 3 file view:
- `session-stats-display.blade.php`: Hiển thị parent node với 2 child nodes
- `session-kpi-hierarchy.blade.php`: Hiển thị parent node với 2 child nodes
- `index.blade.php`: Cập nhật phần custom period với Alpine.js templates

### 3. Giao diện mới
```
⏳ X ca chưa có dữ liệu trả về (thông thường Classin sẽ gửi data về sau mỗi 20ph)
    🕐 Y ca đang chờ data của ClassIn về
    ⚠️ Z ca KHÔNG thấy có data trên ClassIn
```

## Files đã sửa
- `src/app/Services/DashboardService.php`
- `src/resources/views/components/session-stats-display.blade.php`
- `src/resources/views/components/session-kpi-hierarchy.blade.php`
- `src/resources/views/dashboard/index.blade.php`