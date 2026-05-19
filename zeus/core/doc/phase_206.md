# Phase 206
- Trong trang `Quản trị GV` (/teacher-management), tại block `🔄 Học viên bị thay đổi Giáo viên` hãy đảm bảo đã hiểu kỹ sql tham khảo: 

```
SELECT ordles_beneficiary_id as student_id, u.user_username, u.user_email,
       u.user_last_name, u.user_first_name, ordles_tlang_id as subject_id,
       tl.tlang_identifier as subject_name,
       count(DISTINCT(ordles_teacher_id)) as teacher_count
FROM `tbl_order_lessons` o
INNER JOIN tbl_teach_languages tl ON o.ordles_tlang_id = tl.tlang_id
INNER JOIN tbl_users u ON u.user_id = o.ordles_beneficiary_id
WHERE `ordles_tlang_id` IN (389,390,392,405,406,407,411,412,413,414,415,416,
                            558,560,562,564,567,568,569,571,572,574,575,576,577,580,581)
  AND `ordles_lesson_starttime` >= '<từ ngày>' AND `ordles_lesson_starttime` <= '<đến ngày>'
  AND `ordles_status` = 3
GROUP BY ordles_beneficiary_id, ordles_tlang_id
ORDER BY `teacher_count` DESC;
```

Trường `teacher_count` là số giáo viên đã dạy cho học viên đó. Lưu ý, học sinh sẽ có giáo viên cố định theo lịch. Hãy làm rõ thêm những tại các lịch cố định đã có sự thay đổi giáo viên. Cần thêm các thông tin liên quan đến việc giáo viên đó xin nghỉ hoặc có lý do của việc thay đổi.

- Cập nhật cả các biểu đồ liên quan. 