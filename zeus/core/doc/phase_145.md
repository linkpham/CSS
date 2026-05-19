# Phase 145 ✅
- Chỉnh sửa lại trang "Chăm sóc CSI" theo các yêu cầu sau:
```
Bổ sung bộ lọc thời gian, from... to để lọc các chỉ số theo từng giai đoạn thời gian
Phần công thức tính điểm sức khỏe, cần lấy theo tỉ lệ ca học thành công chứ ko lấy theo số ca học thành công ạ (Ví dụ HV có 4 ca học, trong đó thành công 3 ca và noshow 1 ca, thì điểm sức khỏe là 75% chứ ko lấy là 100 - 10 =90).
Bổ sung số buổi học trung bình trong tuần của toàn bộ HV thỏa mãn theo bộ lọc
Sửa màu của các học viên khỏe mạnh này thành 1 màu xanh thật "khỏe mạnh" chứ ko để đỏ.  
```

## Thay đổi

### 1. Bộ lọc thời gian (from...to)
- Thêm 2 input date "Từ ngày" và "Đến ngày" vào phần bộ lọc
- Mặc định: từ 2025-11-04 đến hiện tại (giữ nguyên hành vi cũ)
- Bộ lọc thời gian áp dụng cho tất cả KPI, biểu đồ, bảng HV, và phần EWS
- Backend: `baseCte()` nhận `$filters` với validate date format Y-m-d
- Files: `CsiService.php`, `CsiController.php`, `csi/index.blade.php`

### 2. Công thức điểm sức khỏe mới
- **Cũ**: `100 - (noshow × 10) - (half × 5)` → có thể âm
- **Mới**: `ROUND(total_success * 100.0 / total_scheduled, 1)` → luôn 0-100%
- VD: 4 buổi, 3 thành công, 1 noshow → 75% (không còn 90)
- Cập nhật tất cả tooltip SQL và mô tả cho công thức mới
- Cập nhật quy tắc tính điểm trong phần "📋 Quy tắc tính điểm"

### 3. Buổi học TB/tuần
- Thêm KPI card "Buổi TB / tuần" vào hàng Session Stats
- Giá trị = trung bình cộng avg_lessons_per_week của tất cả HV thỏa mãn bộ lọc
- Backend: thêm `ROUND(AVG(avg_lessons_per_week), 2)` vào summary query

### 4. Màu xanh cho HV khỏe mạnh
- Thêm background xanh nhạt `bg-emerald-500/5` cho row HV "Xanh (Khỏe mạnh)" trong bảng
- Hiển thị "%" sau điểm sức khỏe trong bảng HV và KPI card
- Giữ nguyên emerald-500 cho tất cả indicator xanh