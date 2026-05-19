# Phase 101

## Yêu cầu
- Bổ sung bảng `📋 Mã Acceptance Code (Mã kết quả ca học)` vào cả trang Quảng trị GV `/teacher-management?program=speakwell`
- Trong trang `/teacher-management?program=speakwell`, cần có bảng danh sách các buổi học bị ảnh hưởng bởi việc giáo viên xin nghỉ lấy từ bảng tbl_teacher_leave_request_sessions. Trong bảng này: tlrs_session_type = 1 là buổi học 1-1, với tlrs_session_type = 1 thì tlrs_session_id chính là tbl_order_lessons.ordles_id. Replacement type: 1=Substitute, 2=Replace trong đó:
 
- 1 tức là dạy thay (dạy tạm 1 vài buổi), 2 tức là thay hẳn giáo viên mới
 
- NULL tức là nghỉ buổi đó, k tìm giáo viên dạy thay
 
Các thông tin về học viên bị ảnh hưởng cần tìm ở trường tlrs_session_info, trong đó có json lưu learner rồi

## Thực hiện

### Backend (Model)
- Tạo `TeacherLeaveRequestSession` model — bảng `tbl_teacher_leave_request_sessions`
  - Session type: 1=Buổi học 1-1, 2=Lớp nhóm
  - Replacement type: 1=Dạy thay (tạm thời), 2=Thay GV mới, NULL=Không thay
  - Cast `tlrs_session_info` → JSON array

### Backend (DashboardService)
- Thêm `getLeaveAffectedSessionsStats()`: thống kê tổng, theo loại buổi, theo loại thay thế
- Thêm `getLeaveAffectedSessionsDetail()`: danh sách chi tiết có phân trang, tìm kiếm, lọc
  - JOIN `tbl_teacher_leave_requests` → thông tin đơn nghỉ + GV
  - JOIN `tbl_users` (teacher) → tên, email GV
  - LEFT JOIN `tbl_order_lessons` → thông tin buổi học (khi type=1)
  - LEFT JOIN `tbl_orders` + `tbl_users` (learner) → thông tin HV fallback
  - Ưu tiên lấy thông tin HV từ `tlrs_session_info` JSON (`learners` array: full_name, email)
  - Lấy giờ học, thời lượng từ JSON `session_start_time`/`session_end_time`, fallback sang `ordles_*`
  - Lấy tên môn học từ JSON `subject_name`

### Backend (DashboardController)
- Truyền `leaveAffectedStats` vào view `teacher-management`
- Thêm `apiLeaveAffectedSessions()` — API endpoint hỗ trợ: page, per_page, search, replacement_type

### Routes
- Thêm route `GET /api/leave-affected-sessions`

### Frontend — teacher-management.blade.php
- Thêm section "📅 Buổi học bị ảnh hưởng do GV xin nghỉ" cho SpeakWell tab
  - Summary cards (7 cols): Tổng buổi, Buổi 1-1, Lớp nhóm, Cần thay thế, Dạy thay, Thay GV mới, Không thay
  - Search input + replacement type filter dropdown + Export Excel button
  - Bảng chi tiết (11 cột): #, GV, HV (+ email + môn học), Ngày, Giờ, Thời lượng, Loại buổi, Loại thay thế, TT Đơn nghỉ, Thời gian nghỉ, TT Buổi học
  - Phân trang (first/prev/next/last)
  - Alpine.js `leaveAffectedSessionsSection()` quản lý state, lazy load via API
  - Export CSV UTF-8 BOM (14 cột bao gồm Môn học)

## Status: ✅ Hoàn thành