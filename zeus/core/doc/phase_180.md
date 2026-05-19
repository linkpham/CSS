# Phase 180 — LCMS Cache TTL 3 giờ

## Yêu cầu
- Toàn bộ các chỉ số trên `Báo cáo Tiến độ Học tập (LCMS)` cần cache lại 3 tiếng 1 lần.

## Thay đổi

### 1. Tăng CACHE_TTL từ 60 phút lên 3 giờ (10800 giây)
- `LcmsService::CACHE_TTL` thay đổi từ `3600` → `10800`

### 2. Thêm caching cho 3 method chưa được cache
- `getSectionTypeDistribution()` → cache key `lcms:section_distribution`
- `getTopStudents()` → cache key `lcms:top_students:{limit}`
- `getAtRiskStudents()` → cache key `lcms:at_risk_students:{limit}`

### 3. Cập nhật `clearLcmsCache()` 
- Thêm 3 cache key mới vào danh sách xóa khi nhấn "Làm mới"

### 4. Cập nhật API `cache_ttl_minutes`
- `apiLcmsCacheRefreshedAt()` trả về `cache_ttl_minutes: 180` (thay vì 30)

## Tổng hợp 9 cache key LCMS (tất cả TTL = 3 giờ)
1. `lcms:overview_stats`
2. `lcms:course_breakdown`
3. `lcms:score_distribution`
4. `lcms:completion_trend`
5. `lcms:enrollment_overview`
6. `lcms:student_demographics`
7. `lcms:section_distribution` ← mới
8. `lcms:top_students:10` ← mới
9. `lcms:at_risk_students:10` ← mới

## Status: ✅ Hoàn thành