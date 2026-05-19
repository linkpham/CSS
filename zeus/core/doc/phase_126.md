# Phase 126 — LCMS: Filter by SPEAKWELL ordles_tlang_id

## Yêu cầu
Mọi chỉ số trong LCMS phải đảm bảo là đúng các chỉ số thuộc khóa học SPEAKWELL với:
```sql
ordles_tlang_id IN (533, 558, 560, 562, 580, 581, 564, 567, 568, 569, 416, 415, 414, 413, 571, 572, 574, 575, 576, 389, 390, 392, 405, 406, 407, 411, 412, 577, 586, 585, 584, 582, 404, 403, 583, 471)
```

## Thay đổi

### 1. `LcmsService.php`
- Thêm constant `SPEAKWELL_SUBJECT_IDS` (36 ordles_tlang_id values) — khớp với `DashboardService::SPEAKWELL_SUBJECT_IDS`
- Thêm helper method `speakwellStudentFilter()` — sinh ra SQL subquery lọc HV theo `ordles_beneficiary_id` từ `tbl_order_lessons` WHERE `ordles_tlang_id IN (...)`
- Áp dụng filter cho tất cả các method:
  - `getGrandTotalStats()` — completion + score queries
  - `getTotalStudents()`
  - `getCourseBreakdownStats()` — student count query
  - `getCourseCompletionStats()`
  - `getCourseScoreStats()`
  - `getStudentStats()` — count + list queries
  - `getSectionTypeDistribution()`
  - `getTopStudents()`
  - `getAtRiskStudents()`
  - `getScoreDistributionByType()`
  - `getCompletionTrend()`
  - `getEnrollmentOverview()`
  - `getStudentDemographics()` — total, gender, assignments, score status queries
  - `getStudentStatsAdvanced()` — count + list queries
  - `getStudentDetailReport()`

### 2. `lcms.blade.php`
- Cập nhật tất cả SQL tooltips (ⓘ) để hiển thị điều kiện filter mới:
  ```sql
  AND ua.usrasi_student_id IN (
    SELECT DISTINCT ol.ordles_beneficiary_id
    FROM tbl_order_lessons ol
    WHERE ol.ordles_tlang_id IN (533,558,...,471))
  ```
- Thêm mô tả "Chỉ tính HV thuộc SPEAKWELL (ordles_tlang_id)" vào các tooltip

### Logic
- LCMS đã filter theo `usrasi_course_id IN (346, 563, 595, 1084)` (LCMS course IDs)
- Phase 126 thêm cross-reference: chỉ tính HV có ít nhất 1 buổi học với `ordles_tlang_id` thuộc danh sách 36 môn SPEAKWELL
- Đảm bảo dữ liệu LCMS chính xác cho sản phẩm SPEAKWELL

## Status: ✅ Hoàn thành
