# Phase 11 - URGENT FIX: Total Completed Sessions Count

## ✅ Completed Fix

### Issue Description
The "Tổng số ca hoàn thành" (Total Completed Sessions) in the `📊 Thống kê theo mã Acceptance Code` section was displaying incorrect values.

**Example:** Yesterday's total completed should be 1466, but was showing 1459 (7 sessions missing).

**Expected SQL:**
```sql
SELECT COUNT(*) FROM tbl_order_lessons 
WHERE DATE(ordles_lesson_starttime) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) 
AND ordles_status = 3 
AND ordles_tlang_id IN (533, 558, 560, 562, 580, 581, 564, 567, 568, 569, 416, 415, 414, 413, 571, 572, 574, 575, 576, 389, 390, 392, 405, 406, 407, 411, 412, 577, 586, 585, 584, 582, 404, 403, 583, 471)
```

### Root Cause Analysis
The `getAcceptanceCodeBreakdown()` function was calculating `total_completed` incorrectly:

**Before (Incorrect):**
```php
// Get all completed lessons with acceptance codes
$breakdown = OrderLessonExtra::...->pluck('count', 'ole_acceptance_code')->toArray();
$totalCompleted = array_sum($breakdown);  // Only counts lessons WITH acceptance code records!
```

This method only counted completed lessons that already had an acceptance code record in `tbl_order_lessons_extras`. Lessons that were completed but hadn't yet been processed for acceptance codes were NOT included.

**After (Correct):**
```php
// Get total completed lessons from tbl_order_lessons directly (accurate count)
// This is the same method used in getSessionSuccessFailureBreakdown
$totalCompleted = OrderLesson::whereBetween('ordles_lesson_starttime', [$startUtc, $endUtc])
    ->whereIn('ordles_tlang_id', self::SPEAKWELL_SUBJECT_IDS)
    ->where('ordles_status', OrderLesson::STATUS_COMPLETED)
    ->count();
```

This now directly queries `tbl_order_lessons` to get the accurate count of ALL completed lessons, matching the behavior in `getSessionSuccessFailureBreakdown()` used by the Session Stats section.

### Files Modified
- `src/app/Services/DashboardService.php` - Fixed `getAcceptanceCodeBreakdown()` method

### Verification
The fix ensures consistency between:
- **📊 Thống kê Ca học** → Uses `getSessionSuccessFailureBreakdown()` → Queries `tbl_order_lessons` directly ✅
- **📊 Thống kê theo mã Acceptance Code** → Uses `getAcceptanceCodeBreakdown()` → Now also queries `tbl_order_lessons` directly ✅

Both sections now show the same "Tổng hoàn thành" value for the same date period. 


