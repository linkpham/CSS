# Phase 216 — Fix Teacher Change Dedup: Remove prev_teacher_id from Grouping Key ✅

## Yêu cầu

Hệ thống đang thể hiện các buổi học tiếp theo như thể đó là các lần đổi giáo viên mới, trong khi thực chất cần phân biệt giữa "một sự kiện đổi giáo viên" và "các buổi học tiếp tục sử dụng giáo viên sau khi đã đổi".

Ví dụ với học viên Bảo Trần (ID: 4018):
- 06/04/2026: Quỳnh Nhi → Bảo Ngọc 10 ✅ (sự kiện đổi GV thực sự)
- 15/04/2026: Bảo Ngọc 10 → Diễm Phương ✅ (sự kiện đổi GV thực sự)
- 20/04/2026: Vẫn hiển thị "PH đổi" ❌ (chỉ là buổi tiếp diễn, không phải sự kiện đổi mới)

**Nguyên nhân gốc:** Dedup key cũ gồm `(student_id, prev_teacher_id, new_teacher_id, month)`. Khi HV có nhiều đơn hàng, cùng một "quyết định đổi GV" có thể tạo ra key khác nhau ở các đơn khác nhau (vì prev_teacher_id khác nhau theo từng đơn).

## Giải pháp

**Bỏ `prev_teacher_id` ra khỏi dedup key**, chỉ giữ `(student_id, new_teacher_id, month)`.

Nguyên tắc: Nếu cùng HV đổi sang cùng GV mới trong cùng tháng (dù từ GV cũ khác nhau ở các đơn khác nhau) thì chỉ tính 1 sự kiện.

### 1. SQL (list view + chart data)

**Trước:**
```sql
GROUP BY student_id, prev_teacher_id, ordles_teacher_id, DATE_FORMAT(ordles_lesson_starttime, '%Y-%m')
```

**Sau:**
```sql
GROUP BY student_id, ordles_teacher_id, DATE_FORMAT(ordles_lesson_starttime, '%Y-%m')
```

Áp dụng cho tất cả 5 CTE `change_events_dedup`:
- `getStudentsWithTeacherChanges()` — list view
- `getStudentTeacherChangeChartData()` — distribution, top students, trend, nationality

### 2. PHP (detail view)

**Trước:**
```php
$changeKey = (int) $row->prev_teacher_id . '-' . (int) $row->ordles_teacher_id . '-' . date('Y-m', ...);
```

**Sau:**
```php
$changeKey = (int) $row->ordles_teacher_id . '-' . date('Y-m', ...);
```

### 3. UI tooltips

Cập nhật tooltip cột "Số lần đổi GV" và tooltip SQL để phản ánh logic dedup mới.

## Kết quả kiểm tra (student 4018 - Bảo Trần)

- **Trước:** ≥3 lần đổi GV (buổi 20/04 bị tính sai)
- **Sau:** 2 lần đổi GV (đúng: 06/04 Quỳnh Nhi→Bảo Ngọc 10, 15/04 Bảo Ngọc 10→Diễm Phương)

## Files changed

- `app/Services/DashboardService.php` — `getStudentsWithTeacherChanges()`, `getStudentTeacherChangeDetail()`, `getStudentTeacherChangeChartData()`
- `resources/views/dashboard/teacher-management.blade.php` — tooltips
