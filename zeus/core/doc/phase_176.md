# Phase 176 — Fix LCMS 524 Timeout & JSON Parse Error

## Vấn đề
- Lỗi `Unexpected token '<', "<!DOCTYPE "... is not valid JSON` khi LCMS overview-filtered API timeout
- Cloudflare trả về HTTP 524 (origin server timeout >100s) thay vì JSON
- Frontend gọi `.json()` trên HTML error page → crash

## Nguyên nhân gốc
- Score queries trong `getGrandTotalStatsFiltered`, `getCourseScoreFiltered`, `getScoreTrend` sử dụng **correlated subqueries lồng 3 cấp** (O(n³))
- Mỗi student-section combination chạy subquery riêng → rất chậm trên dữ liệu lớn
- Khi cache hết hạn, queries chạy >100s → Cloudflare 524

## Giải pháp

### 1. Tối ưu SQL: Flat JOIN + UNION ALL thay thế correlated subqueries
- **Trước**: Correlated subquery chạy cho MỖI dòng trong outer query
- **Sau**: Pre-compute quiz scores bằng UNION ALL (1 lần), JOIN kết quả
- Áp dụng cho: `getGrandTotalStatsFiltered`, `getCourseScoreFiltered`, `getScoreTrend`

### 2. Tăng PHP timeout
- Thêm `set_time_limit(120)` cho các API endpoints:
  - `apiLcmsOverviewFiltered`
  - `apiLcmsCourseBreakdownFiltered`
  - `apiLcmsScoreTrend`

### 3. Frontend: Safe JSON parsing
- Thêm helper `safeJsonParse(res, label)` xử lý:
  - HTTP 524 timeout → thông báo rõ ràng
  - HTML error pages (<!DOCTYPE) → thông báo thân thiện
  - Invalid JSON → log chi tiết + thông báo
- Áp dụng cho TẤT CẢ fetch calls trong LCMS blade (15+ endpoints)

## Files thay đổi
- `src/app/Services/LcmsService.php` — Tối ưu 3 score queries
- `src/app/Http/Controllers/DashboardController.php` — set_time_limit
- `src/resources/views/dashboard/lcms.blade.php` — safeJsonParse helper + cập nhật tất cả fetch calls