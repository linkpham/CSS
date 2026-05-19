# Phase 207 — Phân biệt đổi GV theo lịch cố định + Quốc tịch GV + Tooltips

## Yêu cầu
- Trong trang `Quản trị GV` (/teacher-management), tại block `🔄 Học viên bị thay đổi Giáo viên`  cần phân biệt thêm rằng số lần đổi giáo viên ở đây là tại các lịch cố định thì giáo viên có sự thay đổi. Vì có những học viên có nhiều giáo viên dạy, nhưng những giáo viên đều dạy cố định vào các lịch sắp xếp sẵn với học viên. 
- Cần bổ sung giải thích ý nghĩa của cột Số lần đổi gv, do nghỉ phép.
- Cần có sự phân loại giáo viên theo quốc tịch Vietnamese, Philippines, Native 1, Native 2. 
- Kiểm tra xem tại sao một số học viên khi click vào nút `👁️ Xem` thì không thấy có thông tin, ví dụ học viên có email `tamhubt2009@gmail.com` khi click vào `👁️ Xem` thì thông tin trống. 
- Kiểm tra, chỉnh sửa, cập nhật và bổ sung biểu đồ có liên quan

## Thay đổi

### 1. DashboardService.php
- Thêm constants: `NATIVE_1_COUNTRY_CODES` (US, GB, UK, CA, AU), `NATIVE_2_COUNTRY_CODES` (NZ, IE, ZA)
- Thêm helper methods: `getTeacherNationalityType()`, `getDayOfWeekLabel()`
- **`getStudentsWithTeacherChanges()`**: Thay đổi LAG partition từ `PARTITION BY student_id` → `PARTITION BY student_id, DAYOFWEEK(starttime), TIME_FORMAT(starttime, '%H:%i')` để chỉ phát hiện thay đổi GV tại cùng lịch cố định
- **`getStudentTeacherChangeDetail()`**: 
  - LAG partition theo schedule slot (day + time)
  - JOIN tbl_countries để lấy quốc tịch GV
  - Thêm `teacher_type`, `prev_teacher_type`, `schedule_slot`, `nationality_breakdown` vào response
  - Xử lý lỗi + thông báo khi không có dữ liệu
- **`getStudentTeacherChangeChartData()`**: 
  - Cập nhật 3 chart queries (distribution, top students, trend) với schedule-based LAG
  - Thêm query mới cho biểu đồ phân loại quốc tịch GV (`teacher_nationality`)

### 2. teacher-management.blade.php
- Cập nhật tooltip SQL trong block title: giải thích schedule-based partition mới
- Thêm tooltip cho cột "Số lần đổi GV": giải thích rằng chỉ tính thay đổi tại lịch cố định
- Thêm tooltip cho cột "Do nghỉ phép": giải thích nguồn dữ liệu leave requests
- Detail modal:
  - Thêm cột "Lịch cố định" (schedule slot) — hiển thị Thứ + Giờ
  - Thêm cột "Quốc tịch" GV (Vietnamese/Philippines/Native 1/Native 2)
  - Hiển thị quốc tịch GV trước khi đổi
  - Thêm nationality breakdown badges trong summary
  - Thêm error handling (`detailError`) + thông báo khi HV không có dữ liệu
- Charts:
  - Thêm biểu đồ "🌍 Quốc tịch GV liên quan" (doughnut chart)
  - Grid charts từ 3 cột → 4 cột
  - Cập nhật title biểu đồ: "theo lịch cố định"

## Trạng thái: ✅ Hoàn thành
