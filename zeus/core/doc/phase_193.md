# Phase 193

- Sai, trả lại code trước phase 192 (# Phase 191). Tôi cần tính toán lại bảng `📋 Bảng số liệu chi tiết` chứ không phải KPI card `🎯 Tỉ lệ Ontrack`. Trên bảng số liệu chi tiết,ontrack = [Tổng học viên có số lượng ca thành công] / [Tổng buổi đã lên lịch trong giai đoạn được lọc] phải lớn 90%. Ví dụ ở tuần `W10 2026`, có `TỔNG BUỔI` đã lên lịch trong giai đoạn được lọc là 8349 và `HV hoạt động`  là 4737 thì `Ontrack` phải là (4737/8349)*100 ~ 56.7%.

## Thực hiện

### 1. Revert Phase 192 (quay về Phase 191)
- **Backend** (`CsiService.php`):
  - `getSummary()`: Hoàn lại `ontrack_rate = ontrack_count / total_active × 100`
  - `getOntrackTrends()`: Hoàn lại student-level calculation (ontrack_score ≥ 90%, ontrack_count / total_active)
- **Frontend** (`csi/index.blade.php`):
  - KPI card `🎯 Tỉ lệ Ontrack`: hiển thị lại `ontrack_count / total_active HV`
  - Chart 7: tooltip hiển thị `HV Ontrack: X / Y HV active`
  - Chart 8: đổi lại thành `HV Ontrack / HV Active` (stacked bar)

### 2. Tính lại cột Ontrack trên `📋 Bảng số liệu chi tiết`
- **Công thức mới**: Ontrack = HV hoạt động (code 12) / Tổng buổi đã lên lịch × 100
- Thêm hàm helper `getTableOntrackRate(period, totalScheduled)` trong JS
- Bảng hiển thị: `XX.X% (total_active / total_scheduled)`
- Excel export: thêm cột `🎯 Ontrack bảng (%)` riêng biệt, giữ nguyên các cột KPI ontrack
