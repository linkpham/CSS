# Phase 102

- Chỉnh sửa bảng `📋 Chi tiết hủy ca học` sao cho đúng hơn. Dưới đây là ví dụ của tổng số ca hủy trong ngày hôm qua `2026-02-26`
```
SELECT 
    *
FROM 
    tbl_order_lessons AS ol
INNER JOIN 
    tbl_session_logs AS sl
    ON ol.ordles_id = sl.sesslog_record_id
WHERE 
    ol.ordles_tlang_id IN (
        533, 558, 560, 562, 580, 581, 564, 567, 568, 569,
        416, 415, 414, 413, 571, 572, 574, 575, 576,
        389, 390, 392, 405, 406, 407, 411, 412,
        577, 586, 585, 584, 582, 404, 403, 583, 471
    )
    AND ol.ordles_status = 4
    AND sl.sesslog_changed_status = 4
    AND ol.ordles_updated > DATE_SUB(ol.ordles_lesson_starttime, INTERVAL 1 DAY)
    AND ol.ordles_lesson_starttime BETWEEN 
        '2026-02-26 00:00:00' 
        AND '2026-02-27 00:00:00';
```
Hãy tự suy luận và giải quyết vấn đề của bảng `📋 Chi tiết hủy ca học`

- Nếu đã hiểu được thế nào ca học bị hủy, hãy sửa lỗi hiển thị `❌ Đã hủy:` tại block `📊 Thống kê Ca học` trong trang KPI. Trong `⚡ Hủy gấp:` chia rõ thêm bao nhiêu nghỉ do Giáo viên, bao nhiêu ca nghỉ do Học sinh, bao nhiêu ca hủy từ Admin.

Không được phép sai sót