# Phase 98 - Cancellation Stats from Session Logs ✅

## Yêu cầu
- Trong trang Vận hành `/daily-ops?program=speakwell`, bổ sung thêm 1 block nằm dưới `📊 Thống kê Ca học (Hôm nay)` để thống kê Tình trạng hủy ca học theo `Hôm nay, Hôm qua, Hôm kia, Tuần này, Tuần trước, Tháng này, Chọn Tuần, Chọn Tháng, 📅 Tùy chọn` giống như phần `📊 Thống kê Ca học` của trang KPI.
- Tình trạng hủy ca học được lấy từ bảng `tbl_session_logs` với `sesslog_changed_status = 4` là trạng thái ca học bị hủy, `sesslog_comment` là lý do, `sesslog_user_type` có giá trị: =1 là hủy từ học sinh, =2 là hủy từ giáo viên, =3 là hủy từ admin (hệ thống).
- `sesslog_record_type = 1` là buổi học 1-1, với `sesslog_record_type =1` thì `sesslog_record_id` chính là `tbl_order_lessons.ordles_id`.
- `sesslog_created` là thời gian thực hiện việc hủy.
- Liên kết với các bảng liên quan để có thể biết được tên giáo viên dạy, tên các học sinh tham gia ca học đó.

## Thực hiện

### Files thay đổi:
1. **`src/app/Models/SessionLog.php`** (NEW) - Eloquent model cho `tbl_session_logs`
2. **`src/app/Services/DashboardService.php`** - Thêm method `getCancellationStats()`
3. **`src/app/Http/Controllers/DashboardController.php`** - Thêm API endpoint `apiCancellationStats()`
4. **`src/routes/web.php`** - Thêm route `GET /api/cancellation-stats`
5. **`src/resources/views/dashboard/daily-ops.blade.php`** - Thêm block UI với time picker

### Chi tiết kỹ thuật:
- **API Endpoint**: `GET /api/cancellation-stats?period=today|yesterday|day_before|this_week|last_week|this_month` hoặc `?from_date=YYYY-MM-DD&to_date=YYYY-MM-DD`
- **SQL Query**: JOIN `tbl_session_logs` → `tbl_order_lessons` → `tbl_orders` → `tbl_users` (teacher) + `tbl_users` (student)
- **Response**: Summary (total, by_student, by_teacher, by_admin) + Detail list (tối đa 500 bản ghi)
- **UI**: 4 summary cards + detail table with export CSV + time period tabs (giống KPI page)


