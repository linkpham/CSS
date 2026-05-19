# Phase 195
- Biểu đồ `🎯 Tỉ lệ Ontrack (%) ` vẫn không thể hiện đúng giá trị OnTrack giống như `📋 Bảng số liệu chi tiết`. Hãy sửa lại.

## Thực hiện

### 1. Backend (`CsiService.php` - `getOntrackTrends()`)
- **Công thức cũ**: `ontrack_rate = ontrack_count / total_active × 100` (HV ontrack ≥90% / HV active)
- **Công thức mới**: `ontrack_rate = total_active / total_scheduled × 100` (HV hoạt động / Tổng buổi)
- Thêm `SUM(total_scheduled) as total_scheduled` vào aggregation query
- Thêm `total_scheduled` vào response array

### 2. Frontend (`csi/index.blade.php`)
- **Chart 7 header tooltip**: Cập nhật công thức mô tả thành `HV hoạt động / Tổng buổi đã lên lịch × 100`
- **Chart 7 JS tooltip**: Đổi từ `HV Ontrack: X / Y HV active` thành `HV Hoạt động: X / Y buổi`
- **Chart 8 tooltip**: Thêm lưu ý về sự khác biệt đơn vị (HV vs buổi)