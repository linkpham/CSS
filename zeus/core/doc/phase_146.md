# Phase 146 ✅
- Tôi muốn xem chi tiết các buổi nghỉ liên tiếp của học sinh khi click vào xem chi tiết từng học sinh trong bảng `🚨 Cảnh báo sớm (EWS) - HV nghỉ liên tiếp`. Ngoài danh sách buổi nghỉ thì có 1 bản đồ visualize trình bày 1 cách khoa học.

## Thay đổi

### 1. Backend API: Chi tiết EWS học viên
- Thêm `getEwsStudentDetail(int $studentId, array $filters)` vào `CsiService.php`
- Trả về thông tin HV (tên, email, SĐT, CSS), danh sách tất cả buổi học với trạng thái (success/noshow/half/unknown), và số buổi nghỉ liên tiếp
- Route: `GET /api/csi/ews/{studentId}/detail`
- Controller: `CsiController@apiEwsDetail`

### 2. Modal chi tiết khi click vào HV trong bảng EWS
- Click vào bất kỳ dòng HV nào trong bảng EWS → mở modal chi tiết
- Modal hiển thị:
  - **KPI cards**: Tổng buổi, Thành công, Noshow, < 1/2 giờ
  - **Cảnh báo mức độ**: Phân loại mức nghiêm trọng theo số buổi nghỉ (≥5: Rất cao, ≥3: Cao, <3: Trung bình)

### 3. Bản đồ buổi học (Timeline Visualization)
- Mỗi buổi học hiển thị dưới dạng ô vuông màu, sắp xếp theo thời gian (trái → phải = cũ → mới)
- Màu sắc: Xanh = thành công, Đỏ = noshow, Cam = < 1/2 giờ, Xám = chưa có dữ liệu
- Các buổi thuộc chuỗi nghỉ liên tiếp hiện tại được đánh dấu viền đỏ
- Hover hiển thị tooltip chi tiết (thời gian + trạng thái)

### 4. Lịch học theo tháng (Monthly Heatmap Calendar)
- Hiển thị lịch theo từng tháng (từ tháng đầu tiên đến tháng cuối cùng có buổi học)
- Mỗi ngày có buổi học được tô màu theo kết quả (xanh/đỏ/cam/xám)
- Header: T2-CN (thứ 2 → Chủ nhật)
- Ngày thuộc chuỗi nghỉ LT đánh dấu viền đỏ

### 5. Bảng danh sách buổi học chi tiết
- Liệt kê tất cả buổi học, sắp xếp từ mới nhất → cũ nhất
- Cột: #, Thời gian, Trạng thái (badge màu), Thuộc chuỗi nghỉ LT (Có/—)
- Các dòng thuộc chuỗi nghỉ LT được highlight nền đỏ nhạt

### Files thay đổi
- `app/Services/CsiService.php` — thêm `getEwsStudentDetail()`
- `app/Http/Controllers/CsiController.php` — thêm `apiEwsDetail()`
- `routes/web.php` — thêm route `/api/csi/ews/{studentId}/detail`
- `resources/views/csi/index.blade.php` — thêm modal, JS methods, click handler