# Phase 189

Thêm 2 chỉ số này vào trang Chăm sóc CSI:
 
Tổng số học viên của sản phẩm Speakwell (bao gồm cả Easy Speak):

```
SELECT DISTINCT u.user_id
FROM tbl_users AS u
INNER JOIN tbl_orders AS o
    ON o.order_user_id = u.user_id
INNER JOIN tbl_order_lessons AS ol
    ON ol.ordles_order_id = o.order_id
WHERE ol.ordles_tlang_id IN (389, 390, 392, 403, 404, 405, 406, 407, 411, 412, 413, 414, 415, 416, 471, 558, 560, 562, 564, 567, 568, 569, 571, 572, 574, 575, 576, 577, 580, 581, 582, 583, 584, 585, 586);
```

Tổng số học viên Active của sản phẩm SpeakWell (gồm cả Easy Speak):
```
SELECT DISTINCT u.user_id
FROM tbl_users AS u
INNER JOIN tbl_orders AS o
    ON o.order_user_id = u.user_id
INNER JOIN tbl_order_lessons AS ol
    ON ol.ordles_order_id = o.order_id
WHERE u.user_lastseen >= NOW() - INTERVAL 30 DAY
  AND ol.ordles_status = 3
  AND ol.ordles_tlang_id IN (389, 390, 392, 403, 404, 405, 406, 407, 411, 412, 413, 414, 415, 416, 471, 558, 560, 562, 564, 567, 568, 569, 571, 572, 574, 575, 576, 577, 580, 581, 582, 583, 584, 585, 586)
  AND ol.ordles_lesson_endtime >= NOW() - INTERVAL 30 DAY
  AND ol.ordles_lesson_endtime <= NOW();
```  

Và thêm số Inactive = Total - Active
