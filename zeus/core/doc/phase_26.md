# Phase 26 - Usage Report (Báo cáo Sử dụng) ✅

## Yêu cầu
- Tạo báo cáo theo cấu trúc như file /Users/que/Downloads/zeus/Bao-cao-su-dung.xlsx với đủ các dòng dữ liệu.
- Nút "Báo cáo sử dụng" được đặt ở trong trang Doanh Thu `/revenue`.

## Ý nghĩa các cột trong file `/Users/que/Downloads/zeus/Bao-cao-su-dung.xlsx`
```
1   Phân loại gói   Mã gói
2   Mã HV   Mã cho từng học viên trên Zeus
3   ID Billing  Chuyển từ mã đơn bên Billing sang
4   Tên gói Tên gói nhỏ nhất học viên mua
5   Item_ID Mã chuyển trên Billing cho từng gói trong đơn hàng
6   Ngày thanh toán Thời gian thanh toán
7   Ngày bắt đầu    Cần định nghĩa trong hợp đồng
8   Ngày kết thúc   Cần định nghĩa trong hợp đồng
9   Ngày hủy    Thời gian cancel gói
10  Trạng thái
11  Giá sau chiết khấu của 1 buổi học
12  Dư đầu kỳ   Số buổi và số tiền còn lại của 1 item ID tính theo thời điểm
13  Mua trong kỳ    Cùng thời gian cut off thì số Mua trong kỳ này = Đơn thành công trên Billing cho các khóa live
14  Nhận chuyển nhượng  Giá trị nhận chuyển nhượng= Giá trị chuyển nhượng
15  Bù buổi do cam kết đầu ra   Bù buổi do hs không đạt điểm theo cam kết, không charge phí đối tượng nào nhưng dùng để đánh giá chất lượng đào tạo
16  Bù buổi do lỗi vận hành/ hệ thống/ do giáo viên Bù buổi do lỗi Vận hành/ Hê thống/ Giáo viên. Số buổi bù này được dùng để charge phí cho đối tượng gây ra lỗi
17  Chuyển nhượng   Số buổi của học sinh được chuyển nhượng sẽ deactive kèm theo sinh coupon để học sinh có thể dùng thanh toán cho Đơn mới của hs đó hoặc Đối trừ sang Đơn mới của học sinh Nhận chuyển nhượng
18  Xóa số dư tài khoản Hs hết thời hạn hợp đồng vẫn còn số buổi chưa xếp lớp-> deactive tài khoản thì số buổi này sẽ ghi nhận doanh thu
19  Hoàn trả    Deactive tài khoản đồng thời sinh ra Phiếu hoàn trả, chi tiền từ tài khoản
```

## Giải pháp đã triển khai

### 1. UsageReportService (`src/app/Services/UsageReportService.php`)
- Service chuyên dụng để tạo báo cáo sử dụng với 46 cột
- Truy vấn dữ liệu từ các bảng:
  - `tbl_order_subscription_plans`: Thông tin gói đăng ký
  - `tbl_orders`: Thông tin đơn hàng, thanh toán
  - `tbl_users`: Thông tin học viên
  - `tbl_subscription_plans`: Chi tiết gói học
- Tính toán theo kỳ:
  - **Dư đầu kỳ**: Số buổi và số tiền còn lại trước ngày bắt đầu kỳ
  - **Mua trong kỳ**: Đơn hàng thành công trong kỳ
  - **Nhận chuyển nhượng**: Buổi học được chuyển từ học viên khác
  - **Bù buổi**: Do cam kết đầu ra hoặc lỗi vận hành
  - **Chuyển nhượng**: Buổi học chuyển cho học viên khác
  - **Xóa số dư**: Buổi học hết hạn
  - **Hoàn trả**: Buổi học được hoàn tiền
  - **Cuối kỳ**: Số dư còn lại cuối kỳ

### 2. Controller Methods (`DashboardController.php`)
- `apiUsageReport()`: API trả về JSON báo cáo
- `apiExportUsageReport()`: API xuất CSV với UTF-8 BOM cho Excel

### 3. Routes (`web.php`)
```php
Route::middleware('can.view.revenue')->group(function () {
    Route::get('/usage-report', [DashboardController::class, 'apiUsageReport']);
    Route::get('/export-usage-report', [DashboardController::class, 'apiExportUsageReport']);
});
```

### 4. UI (`revenue.blade.php`)
- Section "Báo cáo sử dụng" với:
  - Date picker cho ngày bắt đầu và kết thúc
  - Nút "Xuất CSV" để download báo cáo
- JavaScript xử lý export với loading state

## Commit
```
feat(revenue): add usage report export feature (Phase 26)

- Add UsageReportService with 46-column report generation
- Add API endpoints for JSON and CSV export
- Add 'Báo cáo sử dụng' section to Revenue page with date picker and export button
- Support period-based calculations: opening balance, purchases, transfers, usage, closing balance
```
