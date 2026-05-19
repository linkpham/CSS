# Phase 113

- Bổ sung vào trang Quản trị giáo viên (/teacher-management?program=speakwell) block Danh sách giáo viên khả dụng theo khung giờ theo yêu cầu (nằm dưới block `📋 Tổng quan Nghỉ phép`) như sau:

1. Hiển thị dạng 1 bảng dạng slot booking layout. 
2. Danh sách các ca trống được lưu trong bảng `tbl_availability` (trong bảng này này có số lượng ca trống của từng khung giờ) 
3. Để có các ca khả dụng thì phải trừ đi các ca có lịch dạy của giáo viên mà đã được book lịch nằm trong 2 bảng: tbl_order_lessons, tbl_group_classes.

Ví dụ: Một vài đoạn code để lấy các ca đã book được tìm thấy trong file `/app/Http/Services/Lesson/Schedule.php`
```
//Mảng các buổi học của giáo viên với vai trò học viên trong khung thời gian xem xét
$windowTimeEvents['teacher_learn_lessons'] = $this->getLearnerLearnLessonsEvents($teacherId, $windowStartAt, $windowEndAt);
$windowTimeEvents['teacher_learn_group_classes'] = $this->getLearnerGroupClassesEvents($teacherId, $windowStartAt, $windowEndAt);
$windowTimeEvents['teacher_learn_subscriptions'] = $this->getLearnerSubscriptionsEvents($teacherId, $windowStartAt, $windowEndAt);
$windowTimeEvents['teacher_learn_subscription_plan'] = $this->getLearnerSubscriptionPlanEvents($teacherId, $windowStartAt, $windowEndAt);

//Mảng các buổi dạy của giáo viên trong khung thời gian xem xét
$windowTimeEvents['teacher_teach_lessons'] = $this->getTeacherTeachLessonsEvents($teacherId, $windowStartAt, $windowEndAt);
$windowTimeEvents['teacher_teach_group_classes'] = $this->getTeacherTeachGroupClassesEvents($teacherId, $windowStartAt, $windowEndAt);
$windowTimeEvents['teacher_teach_subscriptions'] = $this->getTeacherTeachSubscriptionsEvents($teacherId, $windowStartAt, $windowEndAt);
$windowTimeEvents['teacher_teach_subscription_plan'] = $this->getTeacherTeachSubscriptionPlanEvents($teacherId, $windowStartAt, $windowEndAt);
$teacherAvailabilities = $this->getTeacherAvailabilitySlots($teacherId, $windowStartAt, $windowEndAt);
```
dòng này là lấy ra tập các ca trống trong rạp: $teacherAvailabilities = $this->getTeacherAvailabilitySlots($teacherId, $windowStartAt, $windowEndAt);
 

 Lấy tập các ca trống ($teacherAvailabilities) trừ đi các ca đã book thì là các giáo viên khả dụng

 Lưu ý: Chỉ lấy giáo viên Speakwell, không được lấy giáo viên OMO bằng cách tìm tập distinct utlang_user_id trong bảng tbl_user_teach_languages thỏa mãn utlang_tlang_id IN (533, 558, 560, 562, 580, 581, 564, 567, 568, 569, 416, 415, 414, 413, 571, 572, 574, 575, 576, 389, 390, 392, 405, 406, 407, 411, 412, 577, 586, 585, 584, 582, 404, 403, 583, 471)

 Thêm cơ chế lọc, tìm kiếm: giáo viên, lựa chọn các khung giờ, lọc bỏ hoặc thêm vào giáo viên có thể dạy trial tức là utlang_tlang_id = 533

 Phải có chức năng export chi tiết ra execl. 
