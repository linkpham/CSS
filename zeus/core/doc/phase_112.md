# Phase 112

- Làm block bảng thống kê nằm trên block `📈 Biểu đồ Doanh thu 30 ngày` trong trang `Doanh thu` (/revenue?program=speakwell) các buổi học trial, bao gồm cả kết quả trial mà giáo viên nhận xét dựa trên query sau:

```sql
SELECT
    ol.ordles_id AS trial_id,
    ol.ordles_beneficiary_id AS trial_user_id,

    -- Thông tin user
    u.user_username AS trial_user_username,
    u.user_email    AS trial_user_email,
    CONCAT(u.user_first_name, ' ', u.user_last_name) AS trial_user_fullname,

    -- Ngày đặt lịch trial (UTC+7)
    DATE(CONVERT_TZ(o.order_addedon, '+00:00', '+07:00')) AS trial_request_date,

    -- Ngày học trial (UTC+7)
    DATE(CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00')) AS trial_date,

    -- Note trial
    ln.lesnote_content AS trial_note,

    NULL AS trial_survey_result,

    -- Chương trình học thực tế trong feedback (tên chương trình)
    JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.name')) AS trial_program_name,

  -- Trạng thái buổi học
    CASE ol.ordles_status
        WHEN 1 THEN 'UNSCHEDULED'
        WHEN 2 THEN 'SCHEDULED'
        WHEN 3 THEN 'COMPLETED'
        WHEN 4 THEN 'CANCELLED'
        ELSE NULL
    END AS trial_status,

    -- Ngày trả kết quả đánh giá của giáo viên (UTC+7)
    DATE(CONVERT_TZ(tf.teafeed_created_at, '+00:00', '+07:00')) AS trial_feedback_date,

    -- Ngôn ngữ của feedback (lang id)
    tf.teafeed_lang_id AS trial_lang_id,

    -- Chuỗi nội dung đánh giá
    CONCAT_WS(
        '\n',
        CONCAT('- Name: ',        JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.name'))),
        CONCAT('- Score: ',       JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.score'))),
        CONCAT('- Level: ',       JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.level'))),
        CONCAT('- Expected: ',    JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.expected'))),
        CONCAT('- Lookup link: ', JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.lookup_link'))),
        CONCAT('- Suggested subject: ',
               JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.suggestions.name'))),
        CONCAT('- Suggested duration: ',
               JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.suggestions.duration'))),
        CONCAT('- Suggested package: ',
               JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.suggestions.package'))),
        CONCAT('- Suggested pathway: ',
               JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.suggestions.pathway'))),
        -- Các dòng assessment: "- General comments: ...", "- Reading skill score: ..."
        (
            SELECT GROUP_CONCAT(
                       CONCAT('- ', jt.ass_name, ': ', jt.ass_value)
                       SEPARATOR '\n'
                   )
            FROM JSON_TABLE(
                tf.teafeed_assessment_detail,
                '$.assessment[*]'
                COLUMNS (
                    ass_name  VARCHAR(255) PATH '$.name',
                    ass_value TEXT         PATH '$.value'
                )
            ) AS jt
        )
    ) AS trial_feedback_content,

    -- Level trên kết quả cuối
    JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.level')) AS trial_feedback_level,

    -- ID môn học được đề xuất
    CAST(
        JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.suggestions.subject'))
        AS UNSIGNED
    ) AS trial_suggested_subject_id,

    -- Tên khóa học được đề xuất
    JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.suggestions.name')) AS trial_suggested_subject_name
FROM tbl_order_lessons AS ol
INNER JOIN tbl_orders AS o
    ON o.order_id = ol.ordles_order_id
LEFT JOIN tbl_users AS u
    ON u.user_id = ol.ordles_beneficiary_id
LEFT JOIN tbl_lesson_notes AS ln
    ON ln.lesnote_ordles_id = ol.ordles_id
LEFT JOIN tbl_teacher_feedbacks AS tf
    ON tf.teafeed_learner_id  = ol.ordles_beneficiary_id
   AND tf.teafeed_record_id   = ol.ordles_id
   AND tf.teafeed_record_type = 1   -- lesson
   AND tf.teafeed_type        = 1   -- trial feedback
WHERE
    ol.ordles_tlang_id = (
        SELECT conf_val
        FROM tbl_configurations
        WHERE conf_name = 'CONF_TRIAL_SUBJECT_ID'
        LIMIT 1
    )
    AND FIND_IN_SET(ol.ordles_status, '1,2,3,4') > 0
  AND ol.ordles_lesson_starttime >= :start_date -- thay thời điểm bắt đầu
    AND ol.ordles_lesson_starttime < :end_date -- thay thời điểm kết thúc
ORDER BY o.order_addedon ASC
```

Lưu ý trường nội dung trong `trial_note` và `trial_feedback_note` có dấu xuống dòng. Cần trình bày bảng sao cho đẹp mắt. 