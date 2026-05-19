# Phase 215 — Event-based Teacher Change Counting & Leave Date Validation ✅

## Yêu cầu

1. **Đếm theo sự kiện, không theo buổi học bị ảnh hưởng**: Mỗi lần thay đổi giáo viên (ví dụ: từ GV A sang GV B) chỉ được ghi nhận là một sự kiện duy nhất, kể cả khi ảnh hưởng nhiều buổi học hoặc nhiều đơn hàng. Các buổi học phía sau được đánh dấu là "cùng sự kiện" thay vì tính là lần đổi mới.

2. **Kiểm tra ngày nghỉ phải giao cắt với buổi học**: Lý do "GV nghỉ phép" chỉ được gắn khi khoảng thời gian nghỉ thực sự trùng với ngày diễn ra buổi học.

## Giải pháp

### 1. Deduplication (gộp sự kiện trùng lặp)

- **SQL (list view + chart data)**: Thêm CTE `change_events_dedup` gộp theo `(student_id, prev_teacher_id, ordles_teacher_id, YYYY-MM)`. Trong cùng một tháng, nếu cùng cặp GV cũ → GV mới xuất hiện ở nhiều đơn hàng, chỉ tính 1 sự kiện.
- **PHP (detail view)**: Sử dụng `$seenChangeEvents` map với key `"prevId-newId-YYYY-MM"`. Lần đầu tiên: `is_change = true`. Các lần sau: `is_change = false, is_change_continuation = true`.

### 2. Leave date overlap validation

- **Method 1 (direct session link)**: Thêm `AND DATE(ol.ordles_lesson_starttime) BETWEEN DATE(lr.tlr_start_date) AND DATE(lr.tlr_end_date)` vào tất cả truy vấn liên kết trực tiếp qua `tbl_teacher_leave_request_sessions`.
- **Method 2 (date-range fallback)**: Đã có sẵn kiểm tra ngày — không cần thay đổi.

### 3. UI changes

- Hàng "cùng sự kiện" được tô nền cam nhạt với nhãn "🔄 Cùng sự kiện" và mô tả "Thuộc cùng sự kiện đổi GV (không tính là lần đổi mới)".
- Tooltip cột "Số lần đổi GV" cập nhật để phản ánh cách đếm theo sự kiện.
- CSV export cũng cập nhật để phân biệt "Cùng sự kiện" vs "PH đổi" vs "GV nghỉ".

## Files changed

- `app/Services/DashboardService.php` — `getStudentsWithTeacherChanges()`, `getStudentTeacherChangeDetail()`, `getStudentTeacherChangeChartData()`
- `resources/views/dashboard/teacher-management.blade.php` — detail table, CSV export, tooltips
