# Phase 177 — Fix LCMS 500 + 524 Errors ✅

## Vấn đề

1. **500 Internal Server Error** trên `POST /api/lcms/homework-quiz-details`
   - Thiếu `set_time_limit()` → PHP timeout khi đợi external LCMS API
   - HTTP timeout chỉ 30s → dễ bị timeout với dữ liệu lớn
   - Param format dùng string-indexed keys (`course_ids[0]`) thay vì native array
   - Chỉ catch `ConnectionException`, không catch `RequestException` và `Throwable`

2. **524 Cloudflare Timeout** trên `GET /api/lcms/course-breakdown-filtered`
   - N+1 query: 6 queries/course × 4 courses = 24 queries riêng lẻ
   - Mỗi score query phức tạp (UNION ALL + multiple JOINs) → tổng thời gian vượt 100s

## Giải pháp

### Fix 1: homework-quiz-details
- Thêm `set_time_limit(120)` vào controller method
- Tăng HTTP timeout 30s → 90s, thêm `connectTimeout(15)`
- Sử dụng native PHP arrays cho params (để Http client serialize đúng)
- Catch `\Throwable` thay vì chỉ `\Exception`, thêm catch `RequestException`
- Log thêm trace và params khi lỗi

### Fix 2: course-breakdown-filtered
- Gộp 24 queries thành 4 queries duy nhất:
  1. Course names (1 query cho tất cả courses)
  2. Student counts per course (1 query GROUP BY course_id)
  3. Completion stats (1 query GROUP BY course_id, section_type)
  4. Score stats (1 query GROUP BY course_id, section_type)
- Kết quả: ~27s (all-time) / ~3.4s (date filter) thay vì timeout

## Files changed
- `src/app/Http/Controllers/DashboardController.php` — `apiLcmsHomeworkQuizDetails()`
- `src/app/Services/LcmsService.php` — `getHomeworkQuizReportDetails()`, `getCourseBreakdownFiltered()`

