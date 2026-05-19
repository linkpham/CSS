# Phase 124 - Hiện chi tiết SQL trong ⓘ tại tất cả block LCMS

## Yêu cầu
- Cần hiện chi tiết các câu lệnh SQL trong phần ⓘ tại tất cả các block của trang LCMS

## Thay đổi

### File: `src/resources/views/dashboard/lcms.blade.php`

Cập nhật tất cả 18 ⓘ tooltip trên trang LCMS để hiển thị chi tiết câu lệnh SQL thực tế từ `LcmsService.php`:

1. **📚 Tổng quan Tiến độ Học tập LCMS** (header) — Thêm SQL cho Tổng học viên + Danh sách khóa
2. **Tổng học viên** — Thêm mới ⓘ tooltip với SQL `COUNT(DISTINCT usrasi_student_id)`
3. **Số khóa học** — Thêm mới ⓘ tooltip với SQL danh sách course IDs
4. **Tỉ lệ hoàn thành BTVN** — Cập nhật SQL completion đầy đủ (subquery + GROUP BY)
5. **Điểm TB BTVN** — Cập nhật SQL score đầy đủ (nested subquery MAX quiz scores)
6. **Tỉ lệ hoàn thành BKT** — Cập nhật SQL completion đầy đủ (cou_section_type = 3)
7. **Điểm TB BKT** — Cập nhật SQL score đầy đủ (cou_section_type = 3)
8. **📊 Chi tiết theo Khóa học** (header) — Thêm 3 SQL blocks: completion, score, student count per course
9. **📝 BTVN** (course table sub-header) — Cập nhật mô tả + SQL logic
10. **📋 Bài kiểm tra** (course table sub-header) — Cập nhật mô tả + SQL logic
11. **🧩 Phân bố Loại nội dung** — Cập nhật SQL đầy đủ với WHERE + IN subquery
12. **📈 Phân bố Điểm số Học viên** — Cập nhật SQL đầy đủ với CASE ranges + nested score subquery
13. **📅 Xu hướng Hoàn thành theo Tháng** — Cập nhật SQL đầy đủ với DATE_FORMAT + filters
14. **🎓 Tình trạng Đăng ký Khóa học** — Cập nhật SQL đầy đủ từ `lcms_course_student`
15. **👥 Thống kê Học viên** — Thêm 3 SQL blocks: HV count, gender distribution, assignments & scores
16. **🏆 Top Học viên Điểm cao** — Cập nhật SQL đầy đủ với ORDER BY + LIMIT
17. **⚠️ Học viên Cần chú ý** — Cập nhật SQL đầy đủ với ORDER BY ASC + LIMIT
18. **👨‍🎓 Chi tiết Tiến độ Học viên** — Thêm 2 SQL blocks: danh sách HV phân trang + stats per HV-course

## Trạng thái
✅ Hoàn thành
