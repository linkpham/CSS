# Phase 179 — Revert Phase 175-178 LCMS Comprehensive Reporting ✅

## Vấn đề

Lỗi 524 Cloudflare Timeout trên endpoint `GET /api/lcms/course-breakdown-filtered` vẫn tiếp tục xảy ra kể từ Phase 175, dù đã thử tối ưu qua 3 phase (176, 177, 178):

```
lcms:3859
Load course breakdown filtered error: Error: [Phân tích khóa học] Server timeout (524). Dữ liệu đang được xử lý, vui lòng thử lại sau.
```

Nguyên nhân gốc: UNION ALL query kết hợp `lcms_student_scores` + `lcms_user_quiz_grades` quá nặng để chạy real-time, vượt quá timeout 100s của Cloudflare.

## Giải pháp

Revert toàn bộ code Phase 175-178 (LCMS Comprehensive Reporting) về trạng thái trước Phase 175. Giữ nguyên tất cả tính năng LCMS có từ trước (Phase 120-122).

### Các thành phần đã xóa

1. **LcmsService.php** — Xóa tất cả methods Phase 175+:
   - `getHomeworkQuizReportDetails()` (proxy LCMS external API)
   - `getOverviewStatsFiltered()`, `getGrandTotalStatsFiltered()`
   - `getCourseBreakdownFiltered()`, `getCourseCompletionFiltered()`, `getCourseScoreFiltered()`
   - `getTotalStudentsFiltered()`, `getCourseStudentCount()`
   - `getCompletionTrendFiltered()`, `getScoreTrend()`
   - `clearLcmsCacheAll()`
   - Xóa import `Http` (không còn dùng)

2. **DashboardController.php** — Xóa tất cả endpoint methods Phase 175+:
   - `apiLcmsOverviewFiltered()`, `apiLcmsCourseBreakdownFiltered()`
   - `apiLcmsCompletionTrendFiltered()`, `apiLcmsScoreTrend()`
   - `apiLcmsHomeworkQuizDetails()`, `apiLcmsClearCacheAll()`

3. **Routes (web.php)** — Xóa 6 routes Phase 175:
   - `/lcms/overview-filtered`, `/lcms/course-breakdown-filtered`
   - `/lcms/completion-trend-filtered`, `/lcms/score-trend`
   - `/lcms/homework-quiz-details`, `/lcms/clear-cache-all`

4. **lcms.blade.php** — Revert về sử dụng endpoints gốc:
   - Xóa Date Range Filter UI (bộ lọc ngày)
   - Xóa Score Trend Over Time chart
   - Xóa BTVN/BKT Detail Report section (API proxy)
   - `init()` → dùng lại `/api/lcms/overview`, `loadCourseBreakdown()`, `loadCompletionTrend()`
   - Xóa tất cả Phase 175 JS state variables và methods

### Tính năng LCMS giữ nguyên (Phase 120-122)

- Grand Overview KPI (tổng quan tiến độ học tập)
- Chi tiết theo Khóa học (course breakdown)
- Phân bố Section, Score, Completion Trend charts
- Enrollment Overview & Student Demographics
- Top Students & At Risk Students
- Chi tiết tiến độ học viên (paginated + advanced search)
- Student Detail Modal (section-level breakdown)
- Cache management (clear + timestamp)

## Files changed
- `src/app/Services/LcmsService.php` — Removed Phase 175+ methods
- `src/app/Http/Controllers/DashboardController.php` — Removed Phase 175+ endpoints
- `src/routes/web.php` — Removed Phase 175+ routes
- `src/resources/views/dashboard/lcms.blade.php` — Reverted to pre-Phase 175 UI/JS
