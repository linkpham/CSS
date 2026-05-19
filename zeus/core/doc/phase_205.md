# Phase 205 — Học viên SPW bị thay đổi GV: Lọc SPW + Completed + Biểu đồ

## Yêu cầu

- Trong trang `Quản trị GV` (/teacher-management), tại block `🔄 Học viên bị thay đổi Giáo viên`:
  - Chỉ xem xét các học sinh **SPW** (SPEAKWELL_ONLY_SUBJECT_IDS)
  - Chỉ tính các buổi học **đã kết thúc** (ordles_status = 3)
- Bổ sung thêm các biểu đồ có liên quan

## Thay đổi

### 1. DashboardService.php
- `getStudentsWithTeacherChanges()`: Đổi `SPEAKWELL_SUBJECT_IDS` → `SPEAKWELL_ONLY_SUBJECT_IDS`, đổi `ordles_status IN (2, 3)` → `ordles_status = 3`
- `getStudentTeacherChangeDetail()`: Tương tự — chỉ SPW, chỉ completed
- Thêm method `getStudentTeacherChangeChartData()`: Trả về 3 loại dữ liệu biểu đồ:
  - **Phân bố số lần đổi GV** (distribution): bao nhiêu HV có 1, 2, 3... lần đổi
  - **Top 10 HV bị đổi GV nhiều nhất** (top_students)
  - **Xu hướng thay đổi GV theo tháng** (trend): số lần đổi + số HV bị ảnh hưởng theo tháng

### 2. DashboardController.php
- Thêm `apiStudentTeacherChangeChartData()`: API endpoint trả về chart data

### 3. Routes (web.php)
- Thêm route `GET /api/student-teacher-change-chart-data`

### 4. Blade template (teacher-management.blade.php)
- Cập nhật title: "Học viên SPW bị thay đổi Giáo viên"
- Cập nhật tooltip SQL: `ordles_status = 3` + `SPW only IDs`
- Thêm 3 biểu đồ Chart.js:
  - **Bar chart**: Phân bố số lần đổi GV
  - **Horizontal bar chart**: Top HV bị đổi GV nhiều nhất (số lần đổi + số GV khác nhau)
  - **Line chart**: Xu hướng thay đổi GV theo tháng (số lần đổi + số HV bị ảnh hưởng)
- Charts tự động load sau khi dữ liệu bảng được tải thành công

## SQL tham khảo
```sql
SELECT ordles_beneficiary_id as student_id, u.user_username, u.user_email,
       u.user_last_name, u.user_first_name, ordles_tlang_id as subject_id,
       tl.tlang_identifier as subject_name,
       count(DISTINCT(ordles_teacher_id)) as teacher_count
FROM `tbl_order_lessons` o
INNER JOIN tbl_teach_languages tl ON o.ordles_tlang_id = tl.tlang_id
INNER JOIN tbl_users u ON u.user_id = o.ordles_beneficiary_id
WHERE `ordles_tlang_id` IN (389,390,392,405,406,407,411,412,413,414,415,416,
                            558,560,562,564,567,568,569,571,572,574,575,576,577,580,581)
  AND `ordles_lesson_starttime` >= '2026-01-01'
  AND `ordles_status` = 3
GROUP BY ordles_beneficiary_id, ordles_tlang_id
ORDER BY `teacher_count` DESC;
```

## Trạng thái: ✅ Hoàn thành
