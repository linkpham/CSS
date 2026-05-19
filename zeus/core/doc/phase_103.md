# Phase 103

- Bổ sung vào trang `Doanh thu` (/revenue?program=speakwell) danh sách các học viên có buổi học thành công sau khi thanh toán, dựa trên câu lệnh sau:
```
WITH first_orders AS (
    SELECT 
        o.order_id, 
        o.order_user_id, 
        o.order_item_count, 
        o.order_net_amount, 
        o.order_addedon, 
        op.ordpay_datetime,
        ROW_NUMBER() OVER (
            PARTITION BY o.order_user_id 
            ORDER BY op.ordpay_datetime ASC, o.order_id ASC
        ) AS rn
    FROM tbl_orders o
    INNER JOIN tbl_order_payments op 
        ON o.order_id = op.ordpay_order_id
    WHERE 
        op.ordpay_pmethod_id = 13
        AND op.ordpay_amount > 0
        AND o.order_net_amount > 0
        AND op.ordpay_datetime >= '2026-01-04 17:00:00'
        AND o.order_payment_status = 1
        AND o.order_status = 2
),
first_lessons AS (
    SELECT
        fo.order_id,
        ol.ordles_id,
        ol.ordles_lesson_starttime,
        ROW_NUMBER() OVER (
            PARTITION BY fo.order_id
            ORDER BY ol.ordles_lesson_starttime ASC, ol.ordles_id ASC
        ) AS rn_lesson
    FROM first_orders fo
    INNER JOIN tbl_order_lessons ol 
        ON ol.ordles_order_id = fo.order_id
    INNER JOIN tbl_order_lessons_extras ole
        ON ole.ole_ordles_id = ol.ordles_id
    WHERE 
        fo.rn = 1
        AND ole.ole_acceptance_code IN (9,12)
)
SELECT 
    fo.order_id, 
    fo.order_user_id, 
    fo.order_item_count, 
    fo.order_net_amount, 
    fo.order_addedon,
    fl.ordles_lesson_starttime AS first_lesson_start_time
FROM first_orders fo
LEFT JOIN first_lessons fl 
    ON fo.order_id = fl.order_id
    AND fl.rn_lesson = 1
WHERE fo.rn = 1
ORDER BY fo.order_addedon;
```

Cần có đầy đủ Họ tên học viên trong danh sách. Cần có chức năng lọc/tìm kiếm trong danh sách và tính năng export ra excel. Đảm bảo có 
ⓘ kèm giải thích và câu lệnh SQL.  Trong giải thích nên có những lưu ý sau:
```
Nếu record có trường `first_lesson_start_time`(`ordles_lesson_starttime`) = NULL có nghĩa là đơn hàng đầu tiên đó bị hủy, chưa học 1 buổi nào. 
 
Case này sẽ gặp khi: 
Sale lên đơn nhưng chọn nhầm giáo viên hoặc chọn nhầm giáo trình. Khi đó vận hành sẽ phải sửa cho sale bằng cách, hủy (cancel) các buổi học vừa mua đi, lấy credit + cấp thêm voucher bù vào để mua 1 đơn mới đúng giáo viên và giáo trình

````


 