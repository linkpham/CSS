# Phase 105
- Trong danh sách block `📋 Đơn hàng đầu tiên & Buổi học thành công sau thanh toán`, thêm bộ lọc liệt kê học viên sau thanh toán theo số ngày chưa xếp lớp theo ngày (DATEDIFF(fl.ordles_lesson_starttime, vfo.order_addedon) AS days_difference) và theo giờ (TIMEDIFF(fl.ordles_lesson_starttime, vfo.order_addedon) AS time_difference.). Số ngày là một number input (input spinner) số tự nhiên, mặc định là 2.
- Bổ sung thêm bộ lọc thời gian mua hàng, lọc được từ ngày .... đến ngày cho danh sách trong block `📋 Đơn hàng đầu tiên & Buổi học thành công sau thanh toán`

