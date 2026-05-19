# Phase 24 - Fix Trial/Regular Feedback Count Logic

## Vấn đề
Số lượng Trial feedback và Regular feedback trong phần "Chất lượng Nhật ký Học tập (Feedback GV)" không chính xác.

### Nguyên nhân
- **Logic sai**: Đang sử dụng `teafeed_type = 1` từ `tbl_teacher_feedbacks` để xác định Trial feedback
- **Logic đúng**: Phải join với `tbl_order_lessons` và kiểm tra `ordles_type = 1` mới là buổi học thử thực sự

## Giải pháp

### 1. Cập nhật TeacherFeedback Model
Thêm 2 scope mới:
- `scopeTrialByLesson()`: Join với `tbl_order_lessons.ordles_type = 1` (Trial lesson)
- `scopeRegularByLesson()`: Join với `tbl_order_lessons.ordles_type = 2` (Regular lesson)

Giữ lại scope cũ (`scopeTrial`, `scopeRegular`) nhưng đánh dấu DEPRECATED.

### 2. Cập nhật DashboardService
Thay thế:
- `TeacherFeedback::trial()` → `TeacherFeedback::trialByLesson()`
- `TeacherFeedback::regular()` → `TeacherFeedback::regularByLesson()`

### 3. Cập nhật SQL Tooltips
Cập nhật SQL trong tooltip để phản ánh đúng query mới:

**Trial Feedback:**
```sql
SELECT COUNT(*) FROM tbl_teacher_feedbacks tf
WHERE tf.teafeed_record_type = 1
AND tf.teafeed_record_id IN (
  SELECT ordles_id FROM tbl_order_lessons
  WHERE ordles_type = 1
)
```

**Regular Feedback:**
```sql
SELECT COUNT(*) FROM tbl_teacher_feedbacks tf
WHERE tf.teafeed_record_type = 1
AND tf.teafeed_record_id IN (
  SELECT ordles_id FROM tbl_order_lessons
  WHERE ordles_type = 2
)
```

## Files Changed
1. `src/app/Models/TeacherFeedback.php` - Added new scopes
2. `src/app/Services/DashboardService.php` - Updated getTeacherFeedbackStats()
3. `src/resources/views/dashboard/quality.blade.php` - Updated SQL tooltips

## Giải thích mối quan hệ dữ liệu
- `tbl_teacher_feedbacks.teafeed_record_id` = `tbl_order_lessons.ordles_id` (khi `teafeed_record_type = 1` là 1-on-1 lesson)
- `tbl_order_lessons.ordles_type`: 1 = Trial, 2 = Regular
- Đây là cách chính xác để xác định feedback thuộc bài học thử hay bài học chính thức