# Phase 35 - Voucher (Coupon) Statistics KPI Block

## Yêu cầu
- Dựa trên hiểu biết về cấu trúc và dữ liệu của các bảng `tbl_coupons`, `tbl_coupons_history`, `tbl_coupon_logs`, `tbl_coupons_lang`, bổ sung các chỉ số liên quan đến việc sử dụng voucher vào bảng KPI
- Block Vouchers đặt dưới `📋 Mã Acceptance Code (Mã kết quả ca học)`
- Bao gồm các bộ lọc: Hôm nay, Hôm qua, Hôm kia, Tuần này, Tuần trước, Tháng này
- Tất cả chỉ số có giải thích `ⓘ` kèm SQL query

## Thực hiện

### 1. Tạo Models mới
- `app/Models/Coupon.php` - Model cho bảng `tbl_coupons`
- `app/Models/CouponHistory.php` - Model cho bảng `tbl_coupons_history`
- `app/Models/CouponLog.php` - Model cho bảng `tbl_coupon_logs`

### 2. Thêm Service Methods (DashboardService.php)
- `getVoucherStatsForRange(Carbon $start, Carbon $end)` - Lấy thống kê voucher cho khoảng thời gian
- `calculateTotalDiscountForRange()` - Tính tổng giá trị giảm giá
- `getTopCouponsForRange()` - Lấy top voucher được sử dụng nhiều nhất
- `getMultiPeriodVoucherStats()` - Lấy thống kê đa kỳ (cached)

### 3. Cập nhật getDashboardIndexData()
- Thêm `voucherStats` vào dữ liệu trả về

### 4. Cập nhật clearDashboardCache()
- Thêm `dashboard.voucher_stats` vào danh sách cache keys

### 5. Tạo Blade Views
- `resources/views/dashboard/partials/voucher-stats-content.blade.php` - Partial hiển thị nội dung voucher stats
- Cập nhật `resources/views/dashboard/index.blade.php` - Thêm block Voucher Statistics

## Các chỉ số hiển thị

### Thống kê theo kỳ (có tabs: Hôm nay, Hôm qua, Hôm kia, Tuần này, Tuần trước, Tháng này)

| Chỉ số | Mô tả | SQL/Công thức |
|--------|-------|---------------|
| Số lần áp dụng voucher | Tổng số lần voucher được áp dụng vào đơn hàng | `SELECT COUNT(*) FROM tbl_coupons_history WHERE couhis_created BETWEEN [start] AND [end]` |
| Đơn hàng có voucher | Số đơn hàng duy nhất có sử dụng voucher | `SELECT COUNT(DISTINCT couhis_order_id) FROM tbl_coupons_history WHERE couhis_created BETWEEN [start] AND [end]` |
| Người dùng sử dụng | Số người dùng duy nhất đã sử dụng voucher | `SELECT COUNT(DISTINCT clog_beneficiary_id) FROM tbl_coupon_logs WHERE clog_action = 4 AND clog_created_at BETWEEN [start] AND [end]` |
| Tổng giảm giá (VND) | Tổng giá trị giảm giá đã áp dụng | Tổng cộng `coupon_discount` từ JSON trong `couhis_coupon` |
| Mã voucher được dùng | Số lượng mã voucher khác nhau được sử dụng | `SELECT COUNT(DISTINCT couhis_coupon_id) FROM tbl_coupons_history WHERE couhis_created BETWEEN [start] AND [end]` |
| Lượt áp dụng mã | Số lần action "áp dụng" được ghi nhận | `SELECT COUNT(*) FROM tbl_coupon_logs WHERE clog_action = 4 AND clog_created_at BETWEEN [start] AND [end]` |
| Lượt hủy áp dụng | Số lần action "hủy áp dụng" được ghi nhận | `SELECT COUNT(*) FROM tbl_coupon_logs WHERE clog_action = 5 AND clog_created_at BETWEEN [start] AND [end]` |
| Lượt áp dụng ròng | Chênh lệch giữa áp dụng và hủy | `(Lượt áp dụng) - (Lượt hủy áp dụng)` |

### Tổng quan hệ thống (all-time)
| Chỉ số | Mô tả | SQL |
|--------|-------|-----|
| Tổng voucher | Tổng số voucher trong hệ thống | `SELECT COUNT(*) FROM tbl_coupons` |
| Đang hoạt động | Voucher đang trong thời gian hiệu lực | `SELECT COUNT(*) FROM tbl_coupons WHERE coupon_active = 1 AND coupon_start_date <= NOW() AND coupon_end_date >= NOW()` |
| Đã hết hạn | Voucher đã quá hạn sử dụng | `SELECT COUNT(*) FROM tbl_coupons WHERE coupon_end_date < NOW()` |
| Tỷ lệ đã sử dụng | % voucher đã có ít nhất 1 lượt dùng | `(Số voucher có coupon_used_uses > 0 / Tổng voucher) × 100` |

### Top Voucher (trong kỳ)
- Bảng hiển thị 5 voucher được sử dụng nhiều nhất
- Cột: Mã, Tên, Loại (% hoặc VND), Giá trị, Lượt dùng

## Ghi chú kỹ thuật

### Cấu trúc bảng tbl_coupon_logs
- `clog_action = 4` → Coupon applied to order
- `clog_action = 5` → Coupon released from order
- `clog_actor_type` → 'user', 'admin', 'system'
- `clog_details` → JSON chứa order_id, beneficiary_id, coupon_history_id

### Cấu trúc bảng tbl_coupons
- `coupon_discount_type = 1` → Giảm theo % 
- `coupon_discount_type = 2` → Giảm số tiền cố định (VND)
- `coupon_used_uses` → Số lần đã sử dụng
- `coupon_max_uses` → Giới hạn tổng số lần dùng

### Cấu trúc bảng tbl_coupons_history
- `couhis_coupon` → JSON chứa thông tin coupon tại thời điểm áp dụng, bao gồm `coupon_discount` (giá trị giảm thực tế)
- `couhis_released` → NULL nếu coupon vẫn đang áp dụng, có giá trị nếu đã hủy

## Status: ✅ Hoàn thành 
