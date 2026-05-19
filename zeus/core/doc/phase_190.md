# Phase 190 — Thêm buổi GV nghỉ phép vào Bản đồ buổi học (Session Map)

## Yêu cầu
- Trong bản đồ buổi học (session map), cần thêm các buổi giáo viên nghỉ phép.

## Thay đổi

### Backend (`CsiService.php`)
1. **Thêm method `getStudentLeaveSessions()`**: Query các buổi GV nghỉ phép ảnh hưởng đến học viên từ `tbl_teacher_leave_request_sessions` + `tbl_teacher_leave_requests` (status 2,3 = đã duyệt).
2. **Cập nhật `getStudentDetail()`**: Trả thêm `leave_sessions` trong response.
3. **Cập nhật `getEwsStudentDetail()`**: Trả thêm `leave_sessions` trong response.

### Frontend (`csi/index.blade.php`)
1. **Thêm helper JS `_mergeLeaveSessions()`**: Merge các buổi GV nghỉ phép vào danh sách buổi học:
   - Buổi leave khớp `lesson_id` → đánh dấu `_is_teacher_leave` trên buổi học hiện có (viền tím).
   - Buổi leave không khớp → thêm block mới với status `teacher_leave` (nền tím).
2. **Cập nhật `buildTimelineData()`**: Merge leave sessions vào EWS timeline.
3. **Cập nhật `buildStudentTimeline()`**: Merge leave sessions vào Student Detail timeline.
4. **Cập nhật `buildCalendarData()`**: Thêm leave sessions vào lịch heatmap tháng.
5. **Cập nhật 2 session map (EWS Detail + Student Detail)**:
   - Legend: thêm "🟣 GV nghỉ phép" (tím).
   - Block: `bg-purple-500` cho buổi GV nghỉ phép thuần, `ring-purple-500` cho buổi học có GV nghỉ phép.
   - Tooltip: hiển thị "GV nghỉ phép" + tên GV.
6. **Cập nhật bảng danh sách buổi học**: Thêm cột "GV nghỉ phép", highlight tím cho dòng có GV nghỉ phép.

## SQL Query mới
```sql
SELECT
    lrs.tlrs_session_id AS lesson_id,
    lrs.tlrs_session_date AS session_date,
    lrs.tlrs_need_replacement,
    lrs.tlrs_replacement_type,
    CONCAT(COALESCE(tu.user_last_name, ''), ' ', COALESCE(tu.user_first_name, '')) AS teacher_name
FROM tbl_teacher_leave_request_sessions lrs
INNER JOIN tbl_teacher_leave_requests lr ON lr.tlr_id = lrs.tlrs_leave_request_id
LEFT JOIN tbl_users tu ON lr.tlr_teacher_id = tu.user_id
WHERE lr.tlr_status IN (2, 3)
  AND lrs.tlrs_session_type = 1
  AND CAST(JSON_UNQUOTE(JSON_EXTRACT(lrs.tlrs_session_info, '$.learners[0].id')) AS UNSIGNED) = <student_id>
  AND lrs.tlrs_session_date >= <date_from>
  AND lrs.tlrs_session_date <= <date_to>
ORDER BY lrs.tlrs_session_date ASC
```

## Files changed
- `src/app/Services/CsiService.php` – thêm `getStudentLeaveSessions()`, cập nhật 2 detail methods
- `src/resources/views/csi/index.blade.php` – cập nhật session map, legend, JS merge logic, bảng buổi học

## Status: ✅ Done
