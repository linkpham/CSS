# Phase 95

## Yêu cầu
- Tại chức năng của nút "Báo cáo sử dụng" được đặt ở trong trang Doanh Thu `/revenue` cần đảm bảo xuất ra file cần loại bỏ các buổi học canceled (sửa lại SQL để loại bỏ các ordles_status = 4). Hãy chỉnh sửa và kiểm tra thật kỹ để không có bất kỳ sai sót gì.

## Kết quả

### Phân tích vấn đề

Nút "Báo cáo sử dụng" trên trang `/revenue` gọi `UsageReportService::getOrderData()` để lấy dữ liệu đơn hàng. Query chính JOIN `tbl_orders` với `tbl_order_lessons` và tính toán:
- `COUNT(ol.ordles_id) as item_count` → Tổng số buổi học (total_sessions)
- `SUM(ol.ordles_amount) as total_amount` → Tổng tiền
- `SUM(ordles_discount) as total_discount` → Tổng giảm giá
- `MIN/MAX(ordles_lesson_starttime/endtime)` → Ngày bắt đầu/kết thúc

**Vấn đề:** Query không có điều kiện lọc `ordles_status`, do đó các buổi học bị hủy (`ordles_status = 4` - CANCELLED) vẫn được đếm vào tổng số buổi, tổng tiền, và các thống kê khác trong file xuất Excel.

### Giải pháp

Thêm điều kiện `WHERE ol.ordles_status != 4` vào query `getOrderData()` trong `UsageReportService.php` để loại bỏ hoàn toàn các buổi học bị hủy khỏi báo cáo.

### Kiểm tra các query liên quan

| Query | Trạng thái | Ghi chú |
|-------|-----------|---------|
| `getOrderData()` | ❌ → ✅ Đã sửa | Thêm `->where('ol.ordles_status', '!=', 4)` |
| `batchGetUsedBeforePeriod()` | ✅ OK | Đã filter `ordles_status = 3` (COMPLETED) |
| `batchGetUsedInPeriod()` | ✅ OK | Đã filter `ordles_status = 3` (COMPLETED) |
| `batchGetRefunds()` | ✅ OK | Chỉ lọc `ordles_refund > 0`, không liên quan status |
| `batchGetReceivedTransfers()` | ✅ OK | Bảng khác (`tbl_zcoupon_transactions`) |
| `batchGetOutgoingTransfers()` | ✅ OK | Bảng khác (`tbl_zcoupon_transactions`) |

### Tác động của thay đổi

Khi loại bỏ `ordles_status = 4`, các trường sau trong file Excel sẽ chính xác hơn:
1. **Số buổi (total_sessions)**: Không còn đếm buổi bị hủy
2. **Tổng tiền (total_amount)**: Không còn cộng tiền buổi bị hủy
3. **Giá/buổi (price_per_lesson)**: Tính chính xác hơn vì mẫu số (số buổi) không bị phồng
4. **Dư đầu kỳ (opening_lessons)**: `total - used_before` sẽ chính xác hơn
5. **Ngày bắt đầu/kết thúc**: Không tính theo buổi bị hủy

### Chi tiết thay đổi

**File:** `src/app/Services/UsageReportService.php`
1. Thêm constant `LESSON_STATUS_CANCELLED = 4`
2. Thêm `->where('ol.ordles_status', '!=', self::LESSON_STATUS_CANCELLED)` vào query `getOrderData()`
3. Cập nhật docblock ghi nhận Phase 95

## Status: ✅ HOÀN THÀNH
