# Phase 4: Quản trị Giáo viên & Bài kiểm tra

## Mục tiêu
- Bổ sung module Quản lý Nghỉ phép Giáo viên (Teacher Leave Management) để theo dõi đơn nghỉ, vi phạm, quota
- Bổ sung chỉ số Bài kiểm tra/Quiz để theo dõi lịch thi, kết quả, tỷ lệ pass/fail
- Tích hợp các chỉ số từ tài liệu phân tích (QTGV module)

## Tham khảo
- Database schema: `/Users/que/Downloads/zeus/zeus_core.sql`
- Dashboard metrics: `/Users/que/Downloads/zeus/Dashboard.md`
- Update notes: `doc/update.md`

## Tasks

### Task 4.1: Tạo Models mới
- [x] `TeacherLeaveRequest` - Model cho bảng tbl_teacher_leave_requests
- [x] `TeacherLeaveQuota` - Model cho bảng tbl_teacher_leave_quotas
- [x] `TeacherLeaveViolation` - Model cho bảng tbl_teacher_leave_violations
- [x] `Quiz` - Model cho bảng tbl_quizzes
- [x] `QuizAttempt` - Model cho bảng tbl_quiz_attempts

### Task 4.2: Mở rộng DashboardService
- [x] `getLeaveRequestStats()` - Thống kê đơn nghỉ phép
- [x] `getLeaveRequestsByStatus()` - Phân bố đơn nghỉ theo trạng thái
- [x] `getRecentLeaveRequests()` - Đơn nghỉ gần đây
- [x] `getLeaveViolationStats()` - Thống kê vi phạm nghỉ phép
- [x] `getTeachersWithMostLeave()` - Top GV theo số ngày nghỉ
- [x] `getLeaveRequestTrend()` - Xu hướng đơn nghỉ 14 ngày
- [x] `getQuizStats()` - Thống kê bài kiểm tra
- [x] `getQuizAttemptStats()` - Thống kê lượt thi
- [x] `getRecentQuizAttempts()` - Bài thi gần đây
- [x] `getQuizPassFailRate()` - Tỷ lệ pass/fail

### Task 4.3: Mở rộng API Endpoints
- [x] `GET /api/leave-stats` - Thống kê nghỉ phép
- [x] `GET /api/leave-trend` - Xu hướng đơn nghỉ
- [x] `GET /api/quiz-stats` - Thống kê bài kiểm tra
- [x] `GET /api/quiz-attempts` - Thống kê lượt thi

### Task 4.4: Tạo Dashboard Views

#### Trang Quản trị GV (teacher-management.blade.php) - MỚI
- [x] **Leave Request Overview** - Cards thống kê đơn nghỉ (pending, approved, rejected, tổng)
- [x] **Leave Status Distribution** - Biểu đồ tròn phân bố trạng thái
- [x] **Leave Request Trend Chart** - Biểu đồ 14 ngày
- [x] **Leave Violation Stats** - Thống kê vi phạm nghỉ phép
- [x] **Top Teachers by Leave Days** - Bảng GV nghỉ nhiều nhất
- [x] **Recent Leave Requests** - Bảng đơn nghỉ gần đây
- [x] **Quiz Overview** - Thống kê tổng quan bài kiểm tra
- [x] **Quiz Pass/Fail Rate** - Tỷ lệ pass/fail
- [x] **Recent Quiz Attempts** - Bảng lượt thi gần đây

### Task 4.5: Cập nhật Navigation
- [x] Thêm menu "Quản trị GV" trong sidebar
- [x] Route `/teacher-management` với tên `dashboard.teacher-management`

## Các chỉ số mới bổ sung

### 1. Leave Request Stats
| Chỉ số | Mô tả |
|--------|-------|
| Tổng đơn nghỉ | Tổng số đơn nghỉ phép |
| Đang chờ duyệt | Đơn có status = pending (1) |
| Đã duyệt tự động | Đơn có status = auto_approved (2) |
| Đã duyệt | Đơn có status = approved (3) |
| Bị từ chối | Đơn có status = rejected (4) |
| Đã hủy | Đơn có status = canceled (5) |
| Nghỉ ngắn hạn | Đơn có leave_type = 1 (≤ 6 ngày) |
| Nghỉ dài hạn | Đơn có leave_type = 2 (≥ 7 ngày) |

### 2. Leave Violation Stats
| Chỉ số | Mô tả |
|--------|-------|
| Tổng vi phạm | Tổng số lượt vi phạm |
| No-show | Vi phạm loại không có mặt |
| Nộp đơn trễ | Vi phạm loại nộp đơn trễ hạn |
| Vượt quota | Vi phạm loại vượt số ngày phép |

### 3. Quiz Stats
| Chỉ số | Mô tả |
|--------|-------|
| Tổng số quiz | Tổng bài kiểm tra |
| Quiz đang hoạt động | Quiz có status = active |
| Tổng lượt thi | Tổng quiz attempts |
| Điểm trung bình | AVG score |
| Tỷ lệ pass | % đạt điểm pass |
| Tỷ lệ fail | % không đạt điểm pass |

### 4. Teacher Leave Quota
| Chỉ số | Mô tả |
|--------|-------|
| Quota tháng | 2 ngày/tháng |
| Quota quý | Max 12 ngày/quý |
| Đã sử dụng | Số ngày đã nghỉ |
| Còn lại | Số ngày còn có thể nghỉ |

## Status: ✅ Hoàn thành

Phase 4 đã hoàn thành với các tính năng:
1. **Teacher Leave Management** - Theo dõi đơn nghỉ phép, vi phạm, quota
2. **Quiz/Exam Stats** - Thống kê bài kiểm tra, tỷ lệ pass/fail
3. **New Models** - 5 models mới (TeacherLeaveRequest, TeacherLeaveQuota, TeacherLeaveViolation, Quiz, QuizAttempt)
4. **Charts** - Biểu đồ phân bố đơn nghỉ, xu hướng 14 ngày
5. **Data Tables** - Top GV nghỉ nhiều, đơn nghỉ gần đây, lượt thi gần đây

## API Endpoints

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/teacher-management` | Trang Quản trị GV |
| GET | `/api/leave-stats` | Thống kê nghỉ phép |
| GET | `/api/leave-trend` | Xu hướng đơn nghỉ |
| GET | `/api/quiz-stats` | Thống kê bài kiểm tra |
| GET | `/api/quiz-attempts` | Thống kê lượt thi |

## Tiếp theo: Phase 5
- Real-time updates với WebSocket
- Export reports (PDF, Excel)
- Custom date range picker
- Advanced filtering options
- Survey/khảo sát integration
