# CSS SPW
## 1. Objective

* Xây dựng web dashboard để theo dõi tình trạng **phục hồi / trượt dốc sức khỏe học tập của học viên** theo tháng, quý hoặc giai đoạn tùy chọn.
* Đánh giá hiệu quả các biện pháp chăm sóc hiện tại tới **tình trạng sức khỏe học viên trong buổi live**.
* Theo dõi tương quan giữa:

  * Tình trạng sức khỏe học viên và tỷ lệ gia hạn.
  * Trải nghiệm giáo viên và tỷ lệ gia hạn.
  * Tình trạng học dở dang dưới 1/2 giờ và tỷ lệ gia hạn.
* Hỗ trợ dự báo **Cash Revenue tháng tiếp theo** dựa trên:

  * Số lượng học viên ở từng tệp sức khỏe.
  * Renewal Rate thường đạt của từng tệp.

## 2. Input

* Nguồn dữ liệu chính là Google Sheets cập nhật realtime.
* Input: File mẫu Google sheet cần đọc hiểu

## 3. Expected Output

Web dashboard gồm các màn hình chính:

### 3.1. Overview Dashboard

Hiển thị tổng quan theo giai đoạn được chọn:

* Tổng số học viên.
* Số học viên theo từng nhóm sức khỏe.
* Tỷ lệ phục hồi.
* Tỷ lệ trượt dốc.
* Tỷ lệ giữ nguyên trạng thái.
* Renewal Rate theo từng nhóm sức khỏe.
* Cash Revenue thực tế.
* Cash Revenue dự báo tháng tiếp theo.

### 3.2. Health Movement Dashboard

Theo dõi chuyển dịch sức khỏe học viên giữa base và target:

* Phục hồi.
* Trượt dốc.
* Giữ nguyên tốt.
* Giữ nguyên xấu.
* Mất dữ liệu / không đủ dữ liệu.
* Các trạng thái gộp khác theo quy định trong file.

Cho phép lọc theo:

* Tháng.
* Quý.
* Giai đoạn tùy chọn.
* Sản phẩm.
* Giáo viên.
* Nhóm học viên.
* Tình trạng học live.

### 3.3. Care Effectiveness Dashboard

Đánh giá hiệu quả chăm sóc:

* Nhóm học viên có chăm sóc.
* Nhóm học viên không có chăm sóc.
* Tỷ lệ phục hồi sau chăm sóc.
* Tỷ lệ trượt dốc sau chăm sóc.
* Renewal Rate của nhóm có chăm sóc.
* Renewal Rate của nhóm không chăm sóc.

### 3.4. Renewal Correlation Dashboard

Theo dõi tương quan giữa sức khỏe và gia hạn:

* Renewal Rate theo từng tệp sức khỏe.
* Renewal Rate theo nhóm có trải nghiệm giáo viên tốt / trung bình / kém.
* Renewal Rate theo nhóm có học dở dang dưới 1/2 giờ.
* So sánh RR giữa các nhóm.

### 3.5. Revenue Forecast Dashboard

Dự báo Cash Revenue tháng tiếp theo:

* Số học viên từng tệp sức khỏe.
* RR lịch sử hoặc RR mặc định của từng tệp.
* Giá trị doanh thu trung bình / học viên.
* Cash Revenue dự báo.
* Cho phép chỉnh giả định RR để chạy kịch bản.

## 4. Acceptance Criteria

* [ ] Web đọc được dữ liệu realtime từ Google Sheets.
* [ ] Người dùng chọn được base period và target period.
* [ ] Hệ thống phân loại đúng học viên vào 6 trạng thái gộp theo quy định trong file.
* [ ] Dashboard hiển thị được số lượng và tỷ lệ học viên phục hồi / trượt dốc / giữ nguyên.
* [ ] Dashboard tính được Renewal Rate theo từng nhóm sức khỏe.
* [ ] Dashboard tính được tương quan giữa trải nghiệm giáo viên và Renewal Rate.
* [ ] Dashboard tính được tương quan giữa học dở dang dưới 1/2 giờ và Renewal Rate.
* [ ] Dashboard dự báo được Cash Revenue tháng tiếp theo theo từng tệp sức khỏe.
* [ ] Người dùng lọc được dữ liệu theo tháng, quý, giai đoạn tùy chọn, sản phẩm, giáo viên.
* [ ] Có thể export dữ liệu dashboard ra Excel hoặc CSV.
* [ ] Khi Google Sheets thay đổi, dashboard cập nhật dữ liệu mà không cần sửa code.

## 5. Constraints

* Không thay đổi cấu trúc dữ liệu gốc trên Google Sheets nếu chưa được phê duyệt.
* Không ghi đè dữ liệu gốc.
* Không tự tạo trạng thái mới ngoài 6 trạng thái gộp đã quy định.
* Không tính sức khỏe ngoài phạm vi dữ liệu buổi live nếu chưa có định nghĩa bổ sung.
* Không sử dụng dữ liệu không có mã học viên để join giữa file sức khỏe và file doanh thu.
* Không tính Renewal Rate nếu thiếu dữ liệu base hoặc target.
* Dashboard phải xử lý được trường hợp thiếu dữ liệu, trùng học viên, sai format ngày tháng.
* Ưu tiên web nhẹ, dễ dùng, tốc độ tải nhanh.

## 6. Permitted Side Effects

* Được tạo các bảng dữ liệu trung gian trong backend hoặc database riêng.
* Được cache dữ liệu từ Google Sheets để tăng tốc độ xử lý.
* Được tạo file export Excel / CSV theo yêu cầu người dùng.
* Được ghi log lỗi dữ liệu để phục vụ kiểm tra.
* Không được ghi ngược dữ liệu vào Google Sheets nguồn nếu chưa có quyền riêng.

## 7. Access Permissions

### Read Access

* Được đọc Google Sheets chứa:

  * Data sức khỏe học viên.
  * Data doanh thu / gia hạn.
  * Bảng quy định 6 trạng thái gộp.
  * Bảng mapping sản phẩm / giáo viên / nhóm học viên nếu có.

### Write Access

* Chỉ được ghi vào:

  * Database nội bộ của web.
  * Bảng cache.
  * File export.
  * Log hệ thống.

### Execute Scope

* Được chạy các tác vụ:

  * Đồng bộ dữ liệu từ Google Sheets.
  * Làm sạch dữ liệu.
  * Join dữ liệu theo mã học viên.
  * Tính toán chỉ số sức khỏe.
  * Tính Renewal Rate.
  * Tính Cash Revenue forecast.
  * Render dashboard.
  * Export báo cáo.

### User Roles

* Admin:

  * Cấu hình nguồn dữ liệu.
  * Cấu hình trạng thái gộp.
  * Xem toàn bộ dashboard.
  * Export dữ liệu.

* Manager:

  * Xem dashboard.
  * Lọc dữ liệu.
  * Export báo cáo.

* Viewer:

  * Chỉ xem dashboard.
  * Không được chỉnh cấu hình.
