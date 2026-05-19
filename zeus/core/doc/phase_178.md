# Phase 178 — Fix LCMS 500 + 524 Errors (Definitive) ✅

## Vấn đề

1. **524 Cloudflare Timeout** trên `GET /api/lcms/course-breakdown-filtered`
   - UNION ALL subquery quét toàn bộ bảng `lcms_student_scores` + `lcms_user_quiz_grades`
   - MySQL không thể push-down join condition vào UNION ALL, phải materialize hết dữ liệu trước khi join
   - Tổng thời gian vượt 100s → Cloudflare timeout 524

2. **500 Internal Server Error** trên `POST /api/lcms/homework-quiz-details`
   - `asForm()` encoding không tương thích với external LCMS API cho nested arrays
   - Không có retry logic khi external API timeout (524)
   - Error handling không phân biệt upstream timeout vs connection error

## Giải pháp

### Fix 1: UNION ALL Pre-filtering (524 Timeout)
- Thêm `WHERE stusco_course_id IN (SELECT cou_id FROM lcms_courses WHERE cou_type = 'quiz' AND cou_visible = 1)` vào mỗi branch của UNION ALL
- MySQL dùng index trên `stusco_course_id` / `usrqgr_quiz_id` để lọc trước, tránh quét toàn bộ bảng
- Áp dụng cho 4 methods: `getGrandTotalStatsFiltered`, `getCourseBreakdownFiltered`, `getCourseScoreFiltered`, `getScoreTrend`
- Kết quả: ~27s (all-time) thay vì timeout 100s+

### Fix 2: homework-quiz-details (500 Error)
- Chuyển từ `asForm()` sang JSON encoding (tiêu chuẩn hơn cho nested arrays)
- Thêm retry logic (2 attempts, backoff 3s) cho timeout và 524
- Fallback tự động sang form encoding nếu JSON trả về 415/422
- Phân loại HTTP status codes rõ hơn: 502 (Bad Gateway), 504 (Gateway Timeout)

### Các cải thiện khác
- Tăng cache TTL từ 30 phút → 60 phút để giảm tần suất query
- Tăng `set_time_limit` lên 300s cho các endpoint nặng
- Frontend: hiển thị thông báo cụ thể cho timeout/connection errors

## Kết quả test LOCAL
- `getCourseBreakdownFiltered(null, null)`: **26.69s** ✅ (< 100s Cloudflare limit)
- `getOverviewStatsFiltered(null, null)`: **28.47s** ✅
- `getScoreTrend(null, null)`: **6.09s** ✅
- `getHomeworkQuizReportDetails([...])`: **1.75s** ✅ (no more 500)

## Files changed
- `src/app/Services/LcmsService.php` — UNION ALL pre-filtering, JSON encoding, retry logic, cache TTL
- `src/app/Http/Controllers/DashboardController.php` — set_time_limit, HTTP status codes
- `src/resources/views/dashboard/lcms.blade.php` — frontend error messages