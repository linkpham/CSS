# Phase 38 - Fix Revenue Export Timeout & Add Class Size Statistics

## Issues Addressed

### 1. Fix 524 Timeout Error in Revenue Export
**Problem:** 
The revenue export feature on `/revenue` page was failing with a 524 error (Cloudflare timeout). The error occurred when exporting usage reports:
```
Failed to load resource: the server responded with a status of 524 ()
Failed to parse response: SyntaxError: Unexpected token '<', "<!DOCTYPE "... is not valid JSON
Export error: Error: Lỗi server (524): Không thể xử lý phản hồi
```

**Root Cause:**
Phase 37 introduced a LEFT JOIN with `tbl_group_classes` to get class size data. The join condition (`grpcls_tlang_id` and `grpcls_teacher_id`) created a Cartesian product because multiple group classes can match the same teacher/subject combination. This exponentially increased the number of rows processed, causing the query to timeout.

**Solution:**
Changed the direct LEFT JOIN to use a subquery with GROUP BY to get only ONE aggregated row per teacher/subject combination:

Before (Phase 37):
```php
->leftJoin('tbl_group_classes as gc', function ($join) {
    $join->on('gc.grpcls_tlang_id', '=', 'ol.ordles_tlang_id')
         ->on('gc.grpcls_teacher_id', '=', 'ol.ordles_teacher_id');
})
```

After (Phase 38):
```php
->leftJoin(DB::raw('(SELECT gc_inner.grpcls_tlang_id, gc_inner.grpcls_teacher_id, MAX(gc_inner.grpcls_total_seats) as grpcls_total_seats FROM tbl_group_classes gc_inner GROUP BY gc_inner.grpcls_tlang_id, gc_inner.grpcls_teacher_id) as gc'), function ($join) {
    $join->on('gc.grpcls_tlang_id', '=', 'ol.ordles_tlang_id')
         ->on('gc.grpcls_teacher_id', '=', 'ol.ordles_teacher_id');
})
```

### 2. Add Class Size Statistics Block
**Requirement:**
On the "👤 Chi tiết Người dùng" block in the dashboard overview, add statistics showing the number of students by class size (1:1, 1:2, 1:3, etc.)

**Solution:**
- Added new method `getStudentsByClassSize()` in `DashboardService.php`
- The method queries students grouped by class size based on `tbl_group_classes.grpcls_total_seats`
- Added new UI block "👥 Học viên theo Size lớp" in `index.blade.php`
- Displays 6 class size categories: 1:1 (Cá nhân), 1:2 (Đôi), 1:3 (Nhóm 3), 1:6 (Nhóm vừa), 1:8 (Nhóm lớn), Group (11+ chỗ)

## Files Changed

### `src/app/Services/UsageReportService.php`
- Updated class docblock with Phase 38 notes
- Changed LEFT JOIN with `tbl_group_classes` to use optimized subquery approach
- Prevents Cartesian product that was causing 524 timeout errors

### `src/app/Services/DashboardService.php`
- Added `getStudentsByClassSize()` method
- Added `'studentsByClassSize' => $this->getStudentsByClassSize()` to `getDashboardIndexData()`
- Uses cached query for performance

### `src/resources/views/dashboard/index.blade.php`
- Added new "👥 Học viên theo Size lớp" statistics block
- Shows 6 class size categories with color-coded cards
- Includes tooltips explaining each size category
- Shows total unique students with lessons

## Class Size Mapping

| grpcls_total_seats | Display Value | Description |
|-------------------|---------------|-------------|
| 1 or NULL | 1:1 | Cá nhân (Individual) |
| 2 | 1:2 | Đôi (Pair) |
| 3 | 1:3 | Nhóm 3 (Small group) |
| 4-6 | 1:6 | Nhóm vừa (Medium group) |
| 7-10 | 1:8 | Nhóm lớn (Large group) |
| 11+ | Group | Lớp học (Class) |

## Testing
- PHP syntax validation passed
- Blade template compilation successful
- View cache cleared
