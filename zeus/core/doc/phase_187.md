# Phase 187
- Tại block Cảnh báo giáo viên thêm phần thống kê số buổi bị ảnh hưởng khi giáo viên xin nghỉ phép/ đóng ca trước khi buổi học diễn ra. Tham khảo query sau:

```
SELECT lrs.*,
    -- session info
    JSON_UNQUOTE(JSON_EXTRACT(lrs.tlrs_session_info, '$.session_id')) AS session_id,
    JSON_UNQUOTE(JSON_EXTRACT(lrs.tlrs_session_info, '$.subject_id')) AS subject_id,
    JSON_UNQUOTE(JSON_EXTRACT(lrs.tlrs_session_info, '$.subject_name')) AS subject_name,
    JSON_UNQUOTE(JSON_EXTRACT(lrs.tlrs_session_info, '$.session_date')) AS session_date,
    JSON_UNQUOTE(JSON_EXTRACT(lrs.tlrs_session_info, '$.session_type')) AS session_type,
    JSON_UNQUOTE(JSON_EXTRACT(lrs.tlrs_session_info, '$.need_replacement')) AS need_replacement,
    JSON_UNQUOTE(JSON_EXTRACT(lrs.tlrs_session_info, '$.session_start_time')) AS session_start_time,
    JSON_UNQUOTE(JSON_EXTRACT(lrs.tlrs_session_info, '$.session_end_time')) AS session_end_time,

    -- learner (lấy learner đầu tiên)
    JSON_UNQUOTE(JSON_EXTRACT(lrs.tlrs_session_info, '$.learners[0].id')) AS learner_id,
    JSON_UNQUOTE(JSON_EXTRACT(lrs.tlrs_session_info, '$.learners[0].username')) AS learner_username,
    JSON_UNQUOTE(JSON_EXTRACT(lrs.tlrs_session_info, '$.learners[0].full_name')) AS learner_full_name,
    JSON_UNQUOTE(JSON_EXTRACT(lrs.tlrs_session_info, '$.learners[0].email')) AS learner_email,

    -- phone info
    JSON_UNQUOTE(JSON_EXTRACT(lrs.tlrs_session_info, '$.learners[0].phone.country_id')) AS learner_phone_country_id,
    JSON_UNQUOTE(JSON_EXTRACT(lrs.tlrs_session_info, '$.learners[0].phone.country_dial_code')) AS learner_phone_dial_code,
    JSON_UNQUOTE(JSON_EXTRACT(lrs.tlrs_session_info, '$.learners[0].phone.phone_number')) AS learner_phone_number,
    JSON_UNQUOTE(JSON_EXTRACT(lrs.tlrs_session_info, '$.learners[0].phone.formatted_phone_number')) AS learner_phone_full
FROM `tbl_teacher_leave_requests` lr INNER JOIN tbl_teacher_leave_request_sessions lrs ON lr.tlr_id = lrs.tlrs_leave_request_id
WHERE lr.tlr_start_date BETWEEN '2026-03-11 17:00:00' AND '2026-03-31 17:00:00'
AND lr.tlr_status IN (2,3)   ORDER BY `tlr_id` DESC;
```

- Kiểm tra lại biểu đồ ontrack, chỉ số ontrack theo không đúng. Ví dụ: W45 2025 có tổng buổi là 43, số buổi thành công là 26 thì không thẻ có OnTrack là 94.7% được. 