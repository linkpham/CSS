# Phase 27 - Fix Usage Report Data Source ✅

## Yêu cầu (Bug fix từ Phase 26)
- "Thông tin gói" phải tra từ orders join sang bảng order_lessons chứ không phải lấy từ tbl_order_subscription_plans. Xem ví dụ:
```
SELECT o.order_id, ol.ordles_tlang_id as subject_id, count(ol.ordles_id) as item_count from tbl_orders o INNER JOIN tbl_order_lessons ol ON ol.ordles_order_id = o.order_id WHERE o.order_payment_status=1 AND o.order_status = 2 GROUP BY o.order_id, ol.ordles_tlang_id;
```
với `o.order_payment_status=1 AND o.order_status = 2` => đây là điều kiện đơn complete

- có 2 bảng tbl_orders JOIN sang bảng tbl_order_payments , với những dòng trong orders có đơn complete, thì join sang bảng tbl_order_payments sẽ có thông tin BILLING trả về

## Giải pháp đã triển khai

### 1. Thay đổi nguồn dữ liệu chính (`UsageReportService.php`)
- **Trước đây**: Query từ `tbl_order_subscription_plans` (không chính xác)
- **Bây giờ**: Query từ `tbl_orders` JOIN `tbl_order_lessons` (chính xác)

### 2. Cập nhật query `getOrderData()`
```php
DB::connection('mysql')
    ->table('tbl_orders as o')
    ->join('tbl_order_lessons as ol', 'ol.ordles_order_id', '=', 'o.order_id')
    ->join('tbl_users as u', 'o.order_user_id', '=', 'u.user_id')
    ->leftJoin('tbl_teach_languages as tl', 'ol.ordles_tlang_id', '=', 'tl.tlang_id')
    ->leftJoin('tbl_order_payments as op', 'op.ordpay_order_id', '=', 'o.order_id')
    // ... select columns ...
    ->where('o.order_payment_status', 1)  // Paid
    ->where('o.order_status', 2)          // Complete
    ->groupBy(['o.order_id', 'ol.ordles_tlang_id', ...])
```

### 3. Thêm thông tin Billing từ `tbl_order_payments`
- `billing_payment_id`: ordpay_id
- `billing_txn_id`: ordpay_txn_id (dùng làm Billing ID)
- `billing_amount`: ordpay_amount
- `billing_response`: ordpay_response (JSON chứa thông tin giao dịch)
- `billing_datetime`: ordpay_datetime

### 4. Group by theo order_id và subject_id (ordles_tlang_id)
- Mỗi dòng trong báo cáo = 1 order + 1 subject (môn học)
- Đếm số buổi học: `COUNT(ol.ordles_id) as item_count`
- Tính tổng tiền: `SUM(ol.ordles_amount) as total_amount`
- Đếm buổi đã hoàn thành: `SUM(CASE WHEN ol.ordles_status = 3 THEN 1 ELSE 0 END) as used_lessons`

### 5. Điều kiện đơn complete
- Thêm constants:
  - `ORDER_PAYMENT_STATUS_PAID = 1`
  - `ORDER_STATUS_COMPLETE = 2`
- Chỉ lấy các đơn hàng đã thanh toán và hoàn thành

### 6. Các methods mới/cập nhật
- `getOrderData()`: Query mới từ orders + order_lessons
- `buildReportRow()`: Xây dựng dữ liệu từ order-lesson
- `determineOrderStatus()`: Xác định trạng thái từ số buổi đã dùng
- `calculatePeriodDataFromOrder()`: Tính toán dữ liệu theo kỳ
- `getOpeningBalanceFromOrder()`: Số dư đầu kỳ
- `getPurchasesInPeriodFromOrder()`: Mua trong kỳ
- `getUsedInPeriodFromOrder()`: Sử dụng trong kỳ
- `getRefundsFromOrder()`: Hoàn tiền từ order_lessons

### 7. Xóa các methods cũ không còn sử dụng
- `getSubscriptionData()`
- `getOpeningBalance()`
- `getPurchasesInPeriod()`
- `getBonusLessons()`
- `getUsedInPeriod()`
- `getBalanceDeletion()`
- `getRefunds()`
- `getDeactive()`
- `getStatusLabel()`

## Commit
```
fix(revenue): correct usage report data source (Phase 27)

- Query from tbl_orders JOIN tbl_order_lessons instead of tbl_order_subscription_plans
- Add billing info from tbl_order_payments (billing_txn_id, amount, response)
- Use complete order condition: order_payment_status=1 AND order_status=2
- Group by order_id and subject_id (ordles_tlang_id)
- Remove obsolete subscription-based methods
```
