# Phase 106

- Chỉnh sửa tính năng: `📋 Báo cáo Sử dụngⓘ` (Xuất báo cáo chi tiết sử dụng gói học theo khoảng thời gian) theo các yêu cầu sau:

1. Giảm bớt số cột của báo cáo:
- Chỉ giữ lại các cột sau: cột A đến cột V (Phân loại gói	Mã HV	Tên HV	Email	Trình độ	Size lớp	Quốc tịch GV	Mã gói Zeus	ID Billing	Tên gói	Số buổi	Item_ID	Ngày thanh toán	Ngày bắt đầu	Ngày kết thúc	Ngày hủy	Trạng thái	Giá/buổi	Dư đầu kỳ - Số buổi	Dư đầu kỳ - Số tiền	Mua trong kỳ - Số buổi	Mua trong kỳ - Số tiền), cột AE (Sử dụng - Số buổi), cột AF(Sử dụng - Số tiền), cột AS (Cuối kỳ - Số buổi), cột AT (Cuối kỳ - Số tiền)
- Thêm cột số buổi hủy (phân biệt rõ buổi dùng và buổi hủy, không gộp chung vào tổng giảm)
- Thêm cột Zeus order id (là tbl_orders.order_id)
 
2. Thay đổi nội dung cột I (ID Billing)
- Nếu là đơn mua qua billing, thì hiển thị ID của Billing (như hiện tại là đang đúng)
- Nếu là đơn import, hiển thị package id import (như hiện tại đang hiển thị là NA hoặc IMPORT_ICC_XXXXXXXXXX). Cách lấy package id import như sau: kiểm tra và parse json trường tbl_orders.order_extra_data, cấu trúc:
{"source": "ICC", "package_id": "ST24168<>45615<>000IBI", "api_version": "v1", "created_via": "lesson_package_csv_import"}
Created_via sẽ có giá trị lesson_package_csv_import, package_id là dữ liệu cần lấy
 
3. Thừa dữ liệu báo cáo:
- Ví dụ chọn kỳ báo cáo từ 1/1/2026 đến 31/1/2026, trong báo cáo vẫn hiện các đơn hàng có ngày thanh toán ở sau ngày 31/1 (ví dụ 26/2, 27/2)
 
4. Điều chỉnh logic hiển thị dư đầu kỳ, tiền dư đầu kỳ:
Ví dụ chọn kỳ báo cáo từ 1/1/2026 đến 31/1/2026. Một đơn hàng mua 96 buổi vào ngày 7/1/2026
Hiển thị hiện tại là: Dư đầu kỳ (cột S) = 96, Số tiền Dư đầu kỳ = giá tiền 96 buổi.
Yêu cầu thay đổi như sau: Dư đầu kỳ (cột S) = 0, Số tiền dư đầu kỳ = 0. Logic của kế toán ở đây là: dư đầu kỳ phải tính số buổi học còn lại của đơn hàng ở đúng thời điểm bắt đầu ngày 1/1/2026, mà ở thời điểm đó đơn hàng chưa phát sinh nên dư đầu kỳ của đơn hàng là 0.