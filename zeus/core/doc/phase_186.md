# Phase 186
- Bổ sung thêm biểu đồ xu hướng OnTrack vào trang Chăm sóc CSI.

## Thực hiện
1. **Backend** (`CsiService.php`): Thêm method `getOntrackTrends()` tính per-period (tuần/tháng) OnTrack data:
   - Mỗi kỳ: tính per-student ontrack_score = code 12 / tổng buổi × 100
   - Active = HV có ít nhất 1 buổi code 12
   - Ontrack = HV có ontrack_score ≥ 90%
   - OnTrack rate = ontrack / active × 100
   - Hỗ trợ filter: date_from, date_to, css_staff

2. **Controller** (`CsiController.php`): Thêm endpoint `apiOntrackTrends()`

3. **Route**: `GET /api/csi/ontrack-trends`

4. **Frontend** (`csi/index.blade.php`):
   - Thêm 2 biểu đồ mới trong mục "Xu hướng theo thời gian":
     - 🎯 Tỉ lệ Ontrack (%): Line chart với fill, Y-axis 0-100%
     - 📊 Số HV Ontrack / Active: Stacked bar chart (Ontrack vs Active chưa ontrack)
   - Thêm cột 🎯 Ontrack vào bảng số liệu chi tiết xu hướng
   - Thêm cột Ontrack (%) vào xuất Excel xu hướng
   - Tooltip giải thích rõ công thức, lưu ý code 12 only
