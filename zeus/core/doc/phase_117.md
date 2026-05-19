# Phase 117

- Điều chỉnh câu lệnh SQL trong `📅 Danh sách GV khả dụng theo khung giờ` để sao cho lọc bỏ thêm các giáo viên không đủ qualified để xuất hiện trong khung tìm kiếm giáo viên dựa trên các điều kiện 
```
AND ts.testat_preference > 0
AND ts.testat_qualification > 0
AND ts.testat_teachlang > 0
AND ts.testat_speaklang > 0
AND ts.testat_availability > 0
```
ở trong bảng tbl_teacher_stats về 0. Khi các trường này = 0 thì giáo viên không đủ qualified để xuất hiện trong khung tìm kiếm giáo viên => không đặt được lịch mới. 


- Danh sách giáo viên trong 1 khung giờ cũng được trình bày với các trường chi tiết hơn. Ví dụ 1 khung giờ sau:

```
SELECT a.avail_user_id as teacher_id, teacher.user_first_name as teacher_name, teacher_settings.user_trial_enabled as teacher_trial, c.country_identifier, ol.ordles_id as lesson_id, ordles_beneficiary_id as student_id,		
CONCAT( IFNULL(student.user_last_name, ''), CASE WHEN student.user_last_name IS NOT NULL AND student.user_first_name IS NOT NULL THEN ' ' ELSE '' END, IFNULL(student.user_first_name, '') ) AS student_full_name, student.user_username, student.user_email, tl.tlang_identifier, ol.ordles_status		
FROM `tbl_availability` a		
	INNER JOIN tbl_teacher_stats ts ON a.avail_user_id = ts.testat_user_id	
	INNER JOIN tbl_users as teacher ON teacher.user_id = a.avail_user_id	
INNER JOIN tbl_user_settings teacher_settings ON a.avail_user_id = teacher_settings.user_id		
INNER JOIN tbl_countries c ON teacher.user_country_id = c.country_id		
	LEFT JOIN tbl_order_lessons ol ON a.avail_user_id = ol.ordles_teacher_id AND a.avail_starttime = ol.ordles_lesson_starttime AND ol.ordles_status IN (2,3)	
	LEFT JOIN tbl_users student ON student.user_id = ol.ordles_beneficiary_id	
	LEFT JOIN  tbl_teach_languages tl ON ol.ordles_tlang_id = tl.tlang_id	
WHERE (ol.ordles_tlang_id IN (533, 558, 560, 562, 580, 581, 564, 567, 568, 569, 416, 415, 414, 413, 571, 572, 574, 575, 576, 389, 390, 392, 405, 406, 407, 411, 412, 577, 586, 585, 584, 582, 404, 403, 583, 471) OR ol.ordles_tlang_id IS NULL)		
AND ts.testat_preference > 0		
AND ts.testat_qualification > 0		
AND ts.testat_teachlang > 0		
AND ts.testat_speaklang > 0		
AND ts.testat_availability > 0		
	AND a.avail_starttime = '2026-03-19 12:10:00'	
ORDER BY lesson_id
```