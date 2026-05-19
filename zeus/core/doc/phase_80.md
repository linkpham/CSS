# Phase 80 - Weekly Unscheduled Breakdown Filter

## Yêu cầu
- Tại Block `📅 Tổng số ca Unscheduled`, thêm một hộp lựa chọn số buổi trung bình mỗi học sinh dự kiến học trong tuần (mặc định là 2). Khi chọn xong nhấn nút Lọc số ca Unscheduled thì sẽ liệt kê ra danh sách toàn bộ số ca Unscheduled theo tuần bằng cách lấy tổng số dự kiến trừ đi số ca Scheduled. Không được phép sai sót, phải có giải thích trong ⓘ kèm SQL

## Thực hiện

### 1. UI Changes (`resources/views/dashboard/daily-ops.blade.php`)
- Thêm dropdown chọn số buổi/tuần (1-7, mặc định: 2)
- Thêm nút "Lọc số ca Unscheduled" màu xanh
- Thêm tooltip ⓘ giải thích công thức tính
- Thêm bảng hiển thị kết quả theo tuần với các cột:
  - Tuần (số tuần trong năm)
  - Từ ngày / Đến ngày
  - HV Active (số học viên đang active)
  - Dự kiến (HV Active × số buổi/tuần)
  - Scheduled (số ca đã lên lịch)
  - Unscheduled (Dự kiến - Scheduled)
- Highlight tuần hiện tại màu xanh lá
- Hiển thị Unscheduled > 0 màu đỏ, ≤ 0 màu xanh

### 2. JavaScript (`teacherCountryUnscheduled()`)
- Thêm state: `lessonsPerWeek`, `weeklyLoading`, `weeklyData`
- Thêm method `fetchWeeklyBreakdown()` gọi API

### 3. API Route (`routes/web.php`)
- `GET /api/weekly-unscheduled-breakdown?lessons_per_week=2`

### 4. Controller (`DashboardController.php`)
- `apiWeeklyUnscheduledBreakdown()` - nhận tham số lessons_per_week

### 5. Service (`WeeklyPlanService.php`)
- `getWeeklyUnscheduledBreakdown($lessonsPerWeek)`:
  - Lấy số HV active (có đơn hàng paid với lessons status IN (1,2,3))
  - Lấy số ca Scheduled theo tuần (status = 2)
  - Tính Unscheduled = (HV active × lessons_per_week) - Scheduled
  - Trả về 12 tuần (4 tuần trước + 8 tuần sau)

## SQL Queries

### Số HV Active
```sql
SELECT COUNT(DISTINCT o.order_user_id) AS count
FROM tbl_orders o
INNER JOIN tbl_order_lessons ol ON o.order_id = ol.ordles_order_id
WHERE o.order_status = 2 
  AND o.order_payment_status = 1
  AND ol.ordles_status IN (1, 2, 3)
  AND ol.ordles_tlang_id IN (533,558,560,...)
```

### Số ca Scheduled theo tuần
```sql
SELECT 
    YEARWEEK(ol.ordles_lesson_starttime, 1) AS year_week,
    COUNT(*) AS scheduled_count
FROM tbl_order_lessons ol
INNER JOIN tbl_orders o ON o.order_id = ol.ordles_order_id
WHERE ol.ordles_status = 2
  AND ol.ordles_tlang_id IN (533,558,560,...)
  AND o.order_status = 2
  AND o.order_payment_status = 1
  AND ol.ordles_lesson_starttime BETWEEN [start] AND [end]
GROUP BY YEARWEEK(ol.ordles_lesson_starttime, 1)
```

## Hoàn thành
- [x] Dropdown chọn số buổi/tuần
- [x] Nút Lọc số ca Unscheduled
- [x] Tooltip ⓘ với SQL query
- [x] Bảng hiển thị kết quả theo tuần
- [x] API endpoint
- [x] Service method với logic tính toán