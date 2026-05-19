# Phase 131 ✅

## Yêu cầu
- `👨‍🎓 Danh sách Học viên`, `🚨 Cảnh báo sớm (EWS) - HV nghỉ liên tiếp` của trang `Chăm sóc CSI` phải có đầy đủ các chức năng tìm kiếm, lọc dữ liệu
- Tại tất cả các block của trang `Chăm sóc CSI` đều phải có `ⓘ` với giải thích và SQL đầy đủ, chi tiết giải thích dữ liệu được lấy ra như thế nào.
- Sửa lỗi dứt điểm Chart.js: `Cannot read properties of null (reading 'save')`

## Thay đổi

### 1. EWS Section - Search/Filter/Sort/Pagination (`csi/index.blade.php`, `CsiController.php`, `CsiService.php`)
- Thêm ô tìm kiếm HV (theo ID, tên, SĐT, email) cho bảng EWS
- Thêm dropdown lọc theo Chuyên viên CSS
- Thêm nút "Xóa bộ lọc" riêng cho EWS
- Thêm sắp xếp theo cột (ID HV, Tên, Buổi nghỉ LT, CSS)
- Thêm phân trang đầy đủ (⏮ ← Trang → ⏭)
- Cập nhật API `/api/csi/ews` nhận params: search, css_staff, page, per_page, sort_by, sort_dir
- Cập nhật `CsiService::getEwsStudents()` hỗ trợ filters, pagination, sort

### 2. Danh sách Học viên
- Đã có đầy đủ search/filter qua bộ lọc chung (nhóm rủi ro, CSS, cảnh báo GV, tra cứu)
- Đã có phân trang và sắp xếp

### 3. Info tooltips ⓘ - Tất cả các block (20 tooltip)
- **Bộ lọc**: Giải thích chức năng lọc
- **KPI Cards** (6): Tổng HV, Khỏe mạnh, Cảnh báo, Báo động, Điểm SK TB, Tỉ lệ TC
- **Session Stats** (5): Tổng buổi XL, Buổi TC, HV Noshow, HV <1/2 giờ, GV Noshow
- **Charts** (4): Phân bố Sức khỏe, Phân bố Điểm, Hiệu suất CSS, Cảnh báo GV
- **CSS Detail Table** (1): Bảng chi tiết theo CSS
- **EWS Section** (1): Cảnh báo sớm
- **Student List** (1): Danh sách Học viên
- **Rules** (1): Quy tắc tính điểm
- Mỗi tooltip có: mô tả dữ liệu, bảng nguồn, SQL query chi tiết

### 4. Fix Chart.js crash (`csi/index.blade.php`)
- Thêm hàm `resetCanvas(id)` thay thế canvas element hoàn toàn trước khi tạo chart mới
- Tránh tình trạng canvas context bị null sau `destroy()` gây lỗi `Cannot read properties of null (reading 'save')`
- Áp dụng cho tất cả 4 chart: healthChart, scoreChart, cssChart, teacherWarningChart
