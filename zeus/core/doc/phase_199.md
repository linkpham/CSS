# Phase 199
- Kiểm tả lại dữ liệu `📊 Thống kê Ca họcⓘ`  trong trang KPI `Tổng quan Hệ thống`. Dữ liệu bị tính sai. Ví dụ `Tổng ca học:` ngày hôm nay ('2026-04-03') phải là 849 theo lệnh SQL:
```
SELECT COUNT(*) FROM tbl_order_lessons

                    WHERE ordles_tlang_id IN (533, 558, 560, 562, 580, 581, 564, 567, 568, 569, 416, 415, 414, 413, 571, 572, 574, 575, 576, 389, 390, 392, 405, 406, 407, 411, 412, 577, 586, 585, 584, 582, 404, 403, 583, 471)

                    AND ordles_status IN (2, 3, 4)

                    AND ordles_lesson_starttime BETWEEN  '2026-04-03 00:00:00' AND '2026-04-03 23:59:59'
```

nhưng trên web lại trả về `607`. Rà soát lại mọi chỉ số để đảm bảo không sai sót.