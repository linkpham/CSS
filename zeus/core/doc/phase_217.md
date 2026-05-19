# Phase 217 — Fix false "PH đổi" classification for teacher leave changes

## Yêu cầu
- Việc hiển thị "PH đổi" đổi với những buổi mà giáo viên đó vẫn bản ghi được lưu ở trong `tbl_teacher_leave_requests` là không đúng. Ví dụ giáo viên có tlr_teacher_id là 8113 (tên `Alma 1`)  và có dữ liệu ở cột `tlr_reason` và có dữ liệu cả ở bảng `tbl_teacher_leave_violations`. Rõ ràng là giáo viên này là nghỉ chứ không phải là do `PH đổi`.  
- Tự kết nối csdl qua `zeus-aurora-cluster-prod.cluster-csrn8dqqphhg.ap-southeast-1.rds.amazonaws.com` để kiểm tra.

## Nguyên nhân
- Khi GV nghỉ phép (ví dụ ngày 4-5/3), hệ thống hủy/chuyển buổi học trong thời gian nghỉ.
- GV thay thế được assign vào buổi học TIẾP THEO (ví dụ ngày 10-17/3), KHÔNG PHẢI ngay trong thời gian nghỉ.
- Logic cũ yêu cầu ngày buổi học phải nằm TRONG khoảng nghỉ phép (`DATE(lesson) BETWEEN DATE(leave_start) AND DATE(leave_end)`), dẫn đến bỏ sót.
- Method 1 (direct session match): Cũng fail vì buổi bị đổi GV có thể được reschedule khiến `ordles_lesson_starttime` khác với thời gian nghỉ gốc.

## Giải pháp
4 vị trí trong `DashboardService.php` được sửa:

1. **Summary CTE (`getStudentsWithTeacherChanges`) — Method 1**: Bỏ date overlap check cho direct session match (link qua `tbl_teacher_leave_request_sessions` đã là bằng chứng xác định).
2. **Summary CTE (`getStudentsWithTeacherChanges`) — Method 2**: Mở rộng window 30 ngày — kiểm tra `tlr_start_date <= lesson_date AND tlr_start_date >= lesson_date - 30 DAY`.
3. **Detail PHP (`getStudentTeacherChangeDetail`) — Method 1**: Bỏ date overlap check (giống #1).
4. **Detail PHP (`getStudentTeacherChangeDetail`) — Method 2**: Mở rộng window 30 ngày (giống #2).
5. **Chart trend CTE (`getStudentTeacherChangeChartData`)**: Cùng logic sửa như #1 và #2.

## Kết quả kiểm tra
- Teacher 8113 (Alma 1): Nghỉ 4-5/3, bị replace cho 7 học sinh (ngày 10-17/3). Trước fix: tất cả hiển thị "PH đổi". Sau fix: tất cả hiển thị "GV nghỉ" ✓
- Không có false positive: kiểm tra teacher 1323 (có leave 7-15/3) cũng match đúng ✓