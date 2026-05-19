# Phase 120
- Đọc file '/Users/que/Downloads/zeus/sql report lcms.xlsx' để đọc hiểu và tạo ra 1 trang báo cáo về tình học học tập của học sinh trên 1 trang mới của SPEAKWELL (trang này đặt tên là LCMS). Hãy cung cấp mọi chỉ số sâu sắc nhất, phù hợp nhất có thể trên trang này.

## Đã hoàn thành

### Files tạo mới:
- `src/app/Services/LcmsService.php` — Service xử lý logic SQL truy vấn dữ liệu LCMS
- `src/resources/views/dashboard/lcms.blade.php` — Giao diện trang LCMS

### Files chỉnh sửa:
- `src/routes/web.php` — Thêm route `/lcms` và 5 API endpoints (`/api/lcms/*`)
- `src/app/Http/Controllers/DashboardController.php` — Thêm 6 methods: `lcms()`, `apiLcmsOverview()`, `apiLcmsCourseBreakdown()`, `apiLcmsStudentStats()`, `apiLcmsTopStudents()`, `apiLcmsAtRiskStudents()`
- `src/resources/views/layouts/app.blade.php` — Thêm link LCMS vào sidebar ICAN

### Tính năng trang LCMS:
1. **Tổng quan KPI** — 6 thẻ: Tổng HV, Số khóa, Tỉ lệ HT BTVN, Điểm TB BTVN, Tỉ lệ HT BKT, Điểm TB BKT
2. **Chi tiết BTVN & BKT** — Tổng lượt giao / hoàn thành / chưa HT cho cả BTVN và BKT
3. **Chi tiết theo Khóa học** — Bảng + biểu đồ Chart.js so sánh tỉ lệ HT và điểm TB giữa các khóa
4. **Top HV Điểm cao** — Xếp hạng 10 HV có điểm TB cao nhất (BTVN + BKT)
5. **HV Cần chú ý** — 10 HV có tỉ lệ hoàn thành thấp nhất, cần hỗ trợ
6. **Bảng chi tiết Học viên** — Phân trang, lọc theo khóa học, tìm kiếm theo Student ID

### SQL Logic (từ file Excel):
- **Bảng**: `lcms_user_assignments`, `lcms_courses`, `lcms_student_scores`
- **Section type**: 2 = BTVN, 3 = Bài kiểm tra
- **Logic hoàn thành**: MIN(usrasi_completion_state) = 1 → Section hoàn thành
- **Logic điểm**: Chỉ tính khi HV làm đủ tất cả Quiz trong Section (COUNT done >= COUNT total)
- **SpeakWell course IDs**: 346, 563, 595, 1084
- **Tooltip SQL**: Mỗi chỉ số đều có info-tooltip giải thích logic SQL

