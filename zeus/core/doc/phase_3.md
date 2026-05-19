# Phase 3: Lộ trình Học tập & Nhật ký Giáo viên

## Mục tiêu
- Bổ sung module Lộ trình Học tập (Learning Path) để theo dõi tiến độ học tập của học viên
- Bổ sung chỉ số Nhật ký Học tập (Teacher Feedback) để theo dõi chất lượng feedback từ giáo viên
- Tích hợp các chỉ số từ tài liệu `doc/update.md` (lotrinhhoc.md & khảo sát pilot)

## Tham khảo
- Database schema: `/Users/que/Downloads/zeus/zeus_core.sql`
- Dashboard metrics: `/Users/que/Downloads/zeus/Dashboard.md`
- Lộ trình học tập & Khảo sát: `doc/update.md`

## Tasks hoàn thành

### Task 3.1: Tạo Models mới ✅
- [x] `TeacherFeedback` - Model cho bảng tbl_teacher_feedbacks
- [x] `Curriculum` - Model cho bảng tbl_curriculum
- [x] `CurriculumSection` - Model cho bảng tbl_curriculum_section
- [x] `CurriculumLecture` - Model cho bảng tbl_curriculum_lecture
- [x] `CurriculumSession` - Model cho bảng tbl_curriculum_session
- [x] `Program` - Model cho bảng tbl_program
- [x] `ProgramUser` - Model cho bảng tbl_program_user

### Task 3.2: Mở rộng DashboardService ✅
- [x] `getLearningPathStats()` - Thống kê lộ trình học tập
- [x] `getCurriculumSessionDistribution()` - Phân bố trạng thái bài học (completed/upcoming/incomplete)
- [x] `getTeacherFeedbackStats()` - Thống kê feedback GV
- [x] `getTeachersFeedbackStatus()` - Trạng thái nộp feedback (pending, on-time rate)
- [x] `getFeedbackSubmissionTrend()` - Xu hướng nộp feedback 14 ngày
- [x] `getTopTeachersByFeedback()` - Top GV theo số feedback
- [x] `getRecentFeedback()` - Feedback gần đây
- [x] `getProgramEnrollmentStats()` - Thống kê chương trình học
- [x] `getCurriculumSessionChartData()` - Biểu đồ bài học lộ trình

### Task 3.3: Mở rộng API Endpoints ✅
- [x] `GET /api/learning-path-stats` - Thống kê lộ trình học tập
- [x] `GET /api/feedback-stats` - Thống kê feedback GV
- [x] `GET /api/feedback-trend` - Xu hướng nộp feedback

### Task 3.4: Tạo Dashboard Views ✅

#### Trang Lộ trình & Nhật ký (learning-path.blade.php) - MỚI
- [x] **Learning Path Overview** - Cards thống kê HV có lộ trình, bài hoàn thành, upcoming, incomplete
- [x] **Session Distribution Chart** - Biểu đồ tròn phân bố trạng thái bài học
- [x] **Program Enrollment** - Danh sách chương trình học và số HV
- [x] **Teacher Feedback Stats** - 7 chỉ số feedback (tổng, chờ duyệt, đã duyệt, trial, regular, hôm nay, tỷ lệ duyệt)
- [x] **Feedback Status Alert** - Cảnh báo GV chưa nộp feedback, tỷ lệ đúng hạn
- [x] **Feedback Submission Trend Chart** - Biểu đồ stacked bar 14 ngày
- [x] **Top Teachers by Feedback** - Bảng top GV theo số feedback
- [x] **Recent Feedback Table** - Bảng feedback gần đây
- [x] **Curriculum Session Chart** - Biểu đồ line 30 ngày

#### Cập nhật Trang Chất lượng (quality.blade.php)
- [x] **Teacher Feedback Quality Section** - 7 chỉ số feedback
- [x] **Feedback Status Alert** - Cảnh báo GV chưa nộp, tỷ lệ đúng hạn

### Task 3.5: Cập nhật Navigation ✅
- [x] Thêm menu "Lộ trình & Nhật ký" trong sidebar
- [x] Route `/learning-path` với tên `dashboard.learning-path`

## Các chỉ số mới bổ sung

### 1. Learning Path Stats
| Chỉ số | Mô tả |
|--------|-------|
| HV có Lộ trình | Số học viên đã được gán program |
| Bài học Hoàn thành | Số curriculum session có status = completed |
| Bài học Upcoming | Số session có status rỗng (chưa học) |
| Bài học Incomplete | Số session có status = incomplete |
| Tỷ lệ hoàn thành | % completed / total sessions |

### 2. Teacher Feedback Stats
| Chỉ số | Mô tả |
|--------|-------|
| Tổng Feedback | Tổng số feedback đã nộp |
| Chờ duyệt | Feedback có status = pending |
| Đã duyệt | Feedback có status = approved |
| Trial | Feedback loại trial |
| Regular | Feedback loại regular |
| Hôm nay | Feedback nộp hôm nay |
| Tỷ lệ duyệt | % approved / total |

### 3. Feedback Status Alert
| Chỉ số | Mô tả |
|--------|-------|
| GV chưa nộp | Bài học completed mà chưa có feedback |
| Nộp hôm nay | Feedback nộp trong ngày |
| Bài cần feedback | Bài học completed cần nộp feedback |
| Tỷ lệ đúng hạn | % feedback nộp đúng deadline (12h ngày T+1) |

### 4. Program Enrollment
| Chỉ số | Mô tả |
|--------|-------|
| Tổng chương trình | Số program có status = published |
| Tổng giáo trình | Số curriculum |
| Top chương trình | Program sorted by users_count |

## Status: ✅ Hoàn thành

Phase 3 đã hoàn thành với các tính năng:
1. **Learning Path Module** - Theo dõi tiến độ lộ trình học tập của HV
2. **Teacher Feedback Quality** - Theo dõi chất lượng nộp feedback của GV
3. **New Models** - 7 models mới cho curriculum, program, feedback
4. **Charts** - Biểu đồ phân bố trạng thái, xu hướng feedback, bài học 30 ngày
5. **Data Tables** - Top GV by feedback, recent feedback, program list
6. **Alerts** - Cảnh báo GV chưa nộp feedback

### Cập nhật thêm (2026-01-07):
7. **Feedback Content Viewer** - Modal hiển thị chi tiết nội dung feedback
8. **Session Outcome Stats** - Thống kê ca học thành công/thất bại/no-show
9. **Session Quality Summary** - Tổng quan chất lượng 30 ngày (Trial vs Regular)
10. **Attendance Issues** - Bảng vấn đề tham gia (GV/HV không vào lớp)

## API Endpoints

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/learning-path` | Trang Lộ trình & Nhật ký |
| GET | `/api/learning-path-stats` | Thống kê lộ trình học tập |
| GET | `/api/feedback-stats` | Thống kê feedback GV |
| GET | `/api/feedback-trend` | Xu hướng nộp feedback |
| GET | `/api/feedback/{id}` | Chi tiết feedback (NEW) |
| GET | `/api/session-outcome` | Thống kê kết quả ca học (NEW) |
| GET | `/api/session-quality` | Tổng quan chất lượng 30 ngày (NEW) |
| GET | `/api/attendance-issues` | Danh sách vấn đề tham gia (NEW) |

## Tiếp theo: Phase 4
- Real-time updates với WebSocket
- Export reports (PDF, Excel)
- Custom date range picker
- Advanced filtering options
- Survey/khảo sát integration (từ lotrinhhoc.md)
