# Phase 121

- Đọc hiểu file '/Users/que/Downloads/zeus/sql report lcms.xlsx' và /Users/que/Downloads/zeus/lcms-schema.md để đọc hiểu và tạo ra 1 trang báo cáo về tình học học tập của học sinh trên 1 trang mới của SPEAKWELL (trang này đặt tên là LCMS). Hãy cung cấp mọi chỉ số sâu sắc nhất, phù hợp nhất có thể trên trang này. Đảm bảo test kỹ và không có bất cứ lỗi gì xảy ra. 

## Đã hoàn thành

### Chỉnh sửa files:
- `src/app/Services/LcmsService.php` — Thêm 6 methods mới + sửa lỗi cột `cou_title` → `cou_name` + cải thiện student lookup
- `src/app/Http/Controllers/DashboardController.php` — Thêm 5 API controller methods
- `src/routes/web.php` — Thêm 5 API routes mới
- `src/resources/views/dashboard/lcms.blade.php` — Thêm 4 section mới với biểu đồ và thống kê

### Tính năng mới (Phase 121):

1. **Phân bố Loại nội dung** (Donut chart) — Biểu đồ phân bố Section theo loại: Bài giảng, BTVN, Bài kiểm tra, Tài nguyên
   - API: `GET /api/lcms/section-distribution`
   - Bảng: `lcms_courses`, `lcms_user_assignments`

2. **Phân bố Điểm số Học viên** (Bar chart) — Histogram phân bố điểm theo 5 khoảng (0-20, 20-40, 40-60, 60-80, 80-100), tách biệt BTVN và BKT
   - API: `GET /api/lcms/score-distribution`
   - Bảng: `lcms_user_assignments`, `lcms_courses`, `lcms_student_scores`

3. **Xu hướng Hoàn thành theo Tháng** (Line chart) — Biểu đồ xu hướng hoàn thành section theo tháng sử dụng `usrasi_completion_time`
   - API: `GET /api/lcms/completion-trend`
   - Bảng: `lcms_user_assignments`, `lcms_courses`

4. **Tình trạng Đăng ký Khóa học** — Thống kê đăng ký, hoàn thành và trạng thái đồng bộ từ bảng `lcms_course_student`
   - API: `GET /api/lcms/enrollment-overview`
   - Bảng: `lcms_course_student`, `lcms_courses`

5. **Thống kê Học viên (Demographics)** — Phân bố giới tính, số HV có/chưa có điểm, tổng lượt giao bài, unique sections
   - API: `GET /api/lcms/student-demographics`
   - Bảng: `lcms_students`, `lcms_user_assignments`, `lcms_student_scores`

### Cải thiện từ Phase 120:

6. **Sửa lỗi `cou_title`** — Column `cou_title` không tồn tại trong `lcms_courses`, đã sửa thành `cou_name`

7. **Cải thiện Student Lookup** — Tra cứu thông tin HV từ `lcms_students` (via `stu_user_id`) trước, fallback sang `tbl_users` (via `user_id`)
   - Hiển thị thêm giới tính (`student_gender`) trong bảng chi tiết
   - Sử dụng đúng column mapping: `usrasi_student_id` → `lcms_students.stu_user_id`

8. **Sửa column tbl_users** — Đổi `usr_name/usr_email` thành `user_first_name + user_last_name / user_email` cho đúng schema

### SQL Logic mới (từ lcms-schema.md):
- **`lcms_students`**: `stu_user_id` ánh xạ tới zeus user ID, dùng cho lookup tên/giới tính/email
- **`lcms_course_student`**: `coustu_course_end` cho biết trạng thái kết thúc khóa, `coustu_is_sync` cho trạng thái đồng bộ
- **`usrasi_completion_time`**: Timestamp hoàn thành dùng cho biểu đồ xu hướng theo tháng
- **Tooltip SQL**: Mỗi chỉ số mới đều có info-tooltip giải thích logic SQL

### Testing:
- PHP syntax check: ✓ Không lỗi cú pháp
- View compilation: ✓ `artisan view:cache` thành công
- Route registration: ✓ 11 routes LCMS (6 cũ + 5 mới)
- Service methods: ✓ Tất cả 10 methods chạy đúng, trả về dữ liệu hợp lệ
