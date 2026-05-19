# Phase 76 - Lọc Unscheduled Sessions theo tháng hiện tại

## Yêu cầu
- Tiếp theo phase 75, số lượng Unscheduled theo tháng không thể lọc qua `ordles_created_date` (trường này không tồn tại trong bảng).
- Cần suy luận cấu trúc CSDL để tìm cách lọc ca học Unscheduled (`ordles_status` = 1) trong tháng hiện tại.

## Phân tích cấu trúc bảng

### 1. Bảng `tbl_order_lessons`:
- **Không có** trường `ordles_created_date`
- `ordles_updated` (datetime): Thời điểm cập nhật bản ghi
- `ordles_lesson_starttime` (datetime): **NULL** với sessions Unscheduled

### 2. Bảng `tbl_orders`:
- `order_addedon` (datetime NOT NULL): **Thời điểm tạo đơn hàng**

## Giải pháp

Với sessions Unscheduled (`ordles_status = 1`), sử dụng `o.order_addedon` từ bảng `tbl_orders` để lọc theo tháng. Đây là thời điểm đơn hàng (và các sessions) được tạo ra.

### SQL Query lọc theo tháng hiện tại:

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
    AND YEAR(o.order_addedon) = YEAR(CURDATE())
    AND MONTH(o.order_addedon) = MONTH(CURDATE())
GROUP BY teacher.user_country_id;
```

### Giải thích:
- `o.order_addedon`: Thời điểm đơn hàng được tạo → đại diện cho thời điểm tạo session
- `YEAR(o.order_addedon) = YEAR(CURDATE())`: Lọc năm hiện tại
- `MONTH(o.order_addedon) = MONTH(CURDATE())`: Lọc tháng hiện tại
- Query tự động cập nhật theo tháng/năm hiện tại khi chạy

### Lưu ý:
- Cách này lọc sessions dựa trên **thời điểm order được tạo**, không phải thời điểm session được schedule
- Với Unscheduled sessions (`ordles_lesson_starttime = NULL`), đây là cách hợp lý nhất để xác định "tháng" của session