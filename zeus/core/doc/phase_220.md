# Phase 220 ✅
- Tốt. Trong `👨‍🎓 Danh sách Học viên` của trang `Chăm sóc CSI`, bổ sung thêm bộ lọc cho Chương trình.

## Thực hiện
- Thêm dropdown bộ lọc `🗣️ Chương trình` (SPEAKWELL / EASYSPEAK) trong khu vực bộ lọc bảng Danh sách Học viên
- Backend: thêm `program` filter vào `extractFilters()` (CsiController) và `buildWhereClause()` (CsiService)
- Frontend: thêm `program` vào `studentFilters`, `buildStudentParams()`, và nút "Xóa bộ lọc"
- Bộ lọc sử dụng `course_names LIKE '%SPEAKWELL%'` hoặc `'%EASYSPEAK%'` trên cột GROUP_CONCAT đã có sẵn từ Phase 219