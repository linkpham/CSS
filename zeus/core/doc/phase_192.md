# Phase 192

 - Sửa lại tỉ lệ học viên ontrack trong `📋 Bảng số liệu chi tiết` và `🎯 Tỉ lệ Ontrack (%)`: Số ca thành công là số ca có code nghiệm thu chỉ là 12. Số học viên ontrack là Tổng học viên có số lượng ca thành công (có code nghiệm thu là 12) trong giai đoạn được lọc từ 90% trở lên (vd có 10 buổi đã lên lịch thì học thảnh công 9 buổi). Cụ thể, active là có buổi học thành công trong thời gian được lọc, còn ontrack là có tỉ lệ buổi học thành công trên 90%.  Tức là, Số học viên ontrack = [Tổng học viên có số lượng ca thành công] / [Tổng buổi đã lên lịch trong giai đoạn được lọc] phải lớn 90%. Ví dụ ở tuần `W10 2026`, có tổng buổi buổi đã lên lịch trong giai đoạn được lọc là 8349 và `HV hoạt động`  là 4737 thì `Ontrack` phải là (4737/8349)*100 ~ 56.7%

## Thực hiện

Công thức mới: **Tỉ lệ Ontrack = HV hoạt động / Tổng HV × 100**
- **HV hoạt động**: HV có ít nhất 1 buổi học có code nghiệm thu = 12 trong giai đoạn lọc
- **Tổng HV**: Tổng học viên có buổi đã lên lịch trong giai đoạn lọc

### Backend (`CsiService.php`)
1. `getSummary()`: Đổi `ontrack_rate` từ `ontrack_count / total_active` → `total_active / total_students`
2. `getOntrackTrends()`: Đổi công thức từ student-level ontrack score (≥90%) → `total_active / total_students` per period

### Frontend (`csi/index.blade.php`)
1. KPI card `🎯 Tỉ lệ Ontrack`: hiển thị `total_active / total_students HV`
2. Bảng số liệu chi tiết: cột 🎯 Ontrack hiển thị `(total_active/total_students)`
3. Chart 7 (Tỉ lệ Ontrack %): tooltip cập nhật `HV hoạt động / Tổng HV`
4. Chart 8: đổi từ "HV Ontrack / HV Active" → "HV Hoạt động / Tổng HV"
5. Excel export: cập nhật headers tương ứng
6. Tất cả tooltips cập nhật giải thích công thức mới