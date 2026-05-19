# Phase 82 - Fix Active Students Definition in Weekly Unscheduled Breakdown

## Yêu cầu
- Trong phần `📊 Ca Unscheduled theo tuần`, Học sinh active là học sinh còn ca chưa học trong tuần (không có status 3 là completed đâu).
- Phải cập nhật giải thích và cung cấp SQL vào ⓘ trong block `📊 Ca Unscheduled theo tuần`


## Thực hiện

### 1. `app/Services/WeeklyPlanService.php`
- Cập nhật query active students: thay đổi từ `status IN (1, 2, 3)` thành `status IN (1, 2)`
- Status 3 (Completed) không còn được tính vào học viên active

### 2. `resources/views/dashboard/daily-ops.blade.php`
- Cập nhật tooltip giải thích rõ ràng hơn:
  - HV active: chỉ tính status 1 (Pending) và 2 (Scheduled)
  - Ghi rõ KHÔNG bao gồm status 3 (Completed)
  - Cập nhật SQL query cho cả HV active và Scheduled/tuần để khớp với code thực tế

## Hoàn thành
✅ Active students chỉ tính học viên còn ca học chưa hoàn thành (status 1, 2)
✅ Tooltip đã được cập nhật với giải thích chi tiết và SQL chính xác
