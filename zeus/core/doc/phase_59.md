# Phase 59
- Bổ sung số ca "Hủy gấp" (kèm giải thích "Hủy gấp" là nghỉ trong vòng 24h trước khi diễn ra buổi học và câu lệnh SQL) ngay dưới "❌ Đã hủy:". Câu lệnh SQL cho số ca hủy gấp cho lớp SPEAKWELL như sau:
```
SELECT COUNT(*) FROM tbl_order_lessons WHERE ordles_tlang_id IN (533, 558, 560, 562, 580, 581, 564, 567, 568, 569, 416, 415, 414, 413, 571, 572, 574, 575, 576, 389, 390, 392, 405, 406, 407, 411, 412, 577, 586, 585, 584, 582, 404, 403, 583, 471) AND `ordles_status` = 4 and ordles_updated > DATE_SUB(ordles_lesson_starttime, INTERVAL 1 DAY) AND DATE(ordles_lesson_starttime) = CURDATE();
```
Anh áp dụng đầy đủ cho cả ```
Hôm nay
Hôm qua
Hôm kia
Tuần này
Tuần trước
Tháng này
📅 Tùy chọn```. Không được phép sai sót. Hãy rà soát, reflect, kiểm tra thật kỹ lưỡng.


