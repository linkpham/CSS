# Phase 75 - Tiếp tục thực hiện yêu cầu mới

## Yêu cầu
Với câu lệnh sau:
```
SELECT teacher.user_country_id     AS teacher_country_id,
    IFNULL(cl.country_name, c.country_identifier) AS teacher_country_name,
	count(ordles_id) as unscheduled_count
FROM `tbl_order_lessons` ol 
	INNER JOIN tbl_orders o ON o.order_id = ol.ordles_order_id 
	INNER JOIN tbl_users teacher
        ON teacher.user_id = ol.ordles_teacher_id
    LEFT JOIN tbl_countries c
        ON c.country_id = teacher.user_country_id
    LEFT JOIN tbl_countries_lang cl
        ON cl.countrylang_country_id = c.country_id
        AND cl.countrylang_lang_id = 1
 WHERE 
	`ordles_status` = 1 
	and ordles_tlang_id IN (558, 560, 562, 580, 581, 564, 567, 568, 569, 416, 415, 414, 413, 571, 572, 574, 575, 576, 389, 390, 392, 405, 406, 407, 411, 412, 577, 586, 585, 584, 582, 404, 403, 583, 471) 
	and o.order_status = 2 
	and o.order_payment_status = 1
GROUP BY teacher.user_country_id;```

Liệu có thể trả về số lượng các buổi unscheduled trong tháng hiện tại được không? Ghi lại vào phase_75.md câu lệnh SQL.

## Giải pháp

Có, có thể lọc các buổi unscheduled trong tháng hiện tại bằng cách thêm điều kiện filter theo `ordles_created_date` (hoặc trường thời gian phù hợp).

### SQL với filter tháng hiện tại:

```sql
SELECT teacher.user_country_id     AS teacher_country_id,
    IFNULL(cl.country_name, c.country_identifier) AS teacher_country_name,
    count(ordles_id) as unscheduled_count
FROM `tbl_order_lessons` ol 
    INNER JOIN tbl_orders o ON o.order_id = ol.ordles_order_id 
    INNER JOIN tbl_users teacher
        ON teacher.user_id = ol.ordles_teacher_id
    LEFT JOIN tbl_countries c
        ON c.country_id = teacher.user_country_id
    LEFT JOIN tbl_countries_lang cl
        ON cl.countrylang_country_id = c.country_id
        AND cl.countrylang_lang_id = 1
WHERE 
    `ordles_status` = 1 
    AND ordles_tlang_id IN (558, 560, 562, 580, 581, 564, 567, 568, 569, 416, 415, 414, 413, 571, 572, 574, 575, 576, 389, 390, 392, 405, 406, 407, 411, 412, 577, 586, 585, 584, 582, 404, 403, 583, 471) 
    AND o.order_status = 2 
    AND o.order_payment_status = 1
    AND YEAR(ol.ordles_created_date) = YEAR(CURDATE())
    AND MONTH(ol.ordles_created_date) = MONTH(CURDATE())
GROUP BY teacher.user_country_id;
```

### Ghi chú:
- Sử dụng `YEAR()` và `MONTH()` với `CURDATE()` để lọc tháng hiện tại
- Nếu cần lọc theo ngày scheduled thay vì ngày tạo, thay `ordles_created_date` bằng trường phù hợp (ví dụ: `ordles_date`)
- Điều kiện này sẽ tự động cập nhật theo tháng hiện tại khi chạy query
