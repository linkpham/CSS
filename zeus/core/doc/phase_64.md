# Phase 64 - Tiếp tục chỉnh sửa phase 63
- Kết quả trong file excel từ "Xuất Plan" cần đánh số thứ tự Tuần giống với phần `🌍 Tổng số ca theo Quốc gia GV (Tuần)`. Kiểm tra lại nhiều lần để đảm bảo số liệu nhất quán, không được sai sót.

## Thay đổi

### WeeklyPlanService.php
- Updated `getWeeksInRange()` to use ISO week number (`isoWeek()`) instead of week-within-month
- Week labels in Excel now show "Tuần X" where X is the ISO week number (e.g., "Tuần 5", "Tuần 6")
- This matches the format used in the "🌍 Tổng số ca theo Quốc gia GV (Tuần)" table which uses `WEEK(date, 3)` in MySQL

## Hoàn thành
✅ Excel export week numbering now matches Teacher Country Weekly Summary table
