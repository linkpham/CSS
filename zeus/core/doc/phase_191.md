# Phase 191

- Chỗ tỉ lệ ontrack theo tuần cần kiểm tra lại. Ví dụ: chỉ số  ontrack theo tháng 3 chỉ đang ~69% nhưng theo tuần đều cao hơn 90%. 

## Nguyên nhân

Tỉ lệ ontrack tổng hợp (KPI card) tính theo **student-level**: % HV active có ontrack_score >= 90%.
Biểu đồ xu hướng ontrack tính theo **session-level**: % buổi có code 12 / tổng buổi mỗi kỳ.
Hai công thức khác nhau nên cho ra kết quả khác nhau.

## Thực hiện

1. **Backend** (`CsiService.php`): Sửa `getOntrackTrends()` từ session-level sang student-level:
   - Mỗi kỳ (tuần/tháng): tính per-student ontrack_score = code 12 / tổng buổi × 100
   - Active = HV có ít nhất 1 buổi code 12 trong kỳ
   - Ontrack = HV có ontrack_score ≥ 90%
   - OnTrack rate = ontrack_count / total_active × 100
   - Cùng công thức với KPI card tổng hợp, nhưng tính riêng theo từng kỳ

2. **Frontend** (`csi/index.blade.php`):
   - Cập nhật tooltip Chart 7 (Tỉ lệ Ontrack) giải thích công thức student-level
   - Đổi Chart 8 từ "Buổi Ontrack / Tổng buổi" → "HV Ontrack / HV Active" (stacked bar)
   - Tooltip Chart 7 hiển thị chi tiết: ontrack_count / total_active
   - Bảng xu hướng hiển thị thêm (ontrack_count/total_active)
   - Excel export thêm cột HV Ontrack, HV Active