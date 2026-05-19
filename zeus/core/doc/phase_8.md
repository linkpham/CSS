# Phase 8 - Dashboard UI Adjustments

## ✅ Completed Features

### 1. Temporarily Hidden "Tháng trước" (Last Month) Button
Hidden the "Tháng trước" tab button in all session statistics sections:
- Session Stats section (main dashboard)
- Never Logged In Students section
- Teacher Login Status section
- Acceptance Codes section

The button and corresponding content are commented out (not deleted) for easy re-enabling later.

### 2. Updated "Tổng Học sinh" Query
Changed the learner count to include users where `user_is_teacher` is NULL.

**Previous SQL:**
```sql
SELECT COUNT(*) FROM tbl_users WHERE user_is_teacher = 0 AND user_deleted IS NULL
```

**New SQL:**
```sql
SELECT COUNT(*) FROM tbl_users WHERE (user_is_teacher = 0 OR user_is_teacher IS NULL) AND user_deleted IS NULL
```

Updated in:
- `src/app/Models/User.php` - `scopeLearners()` method
- `src/resources/views/dashboard/index.blade.php` - SQL tooltip (2 locations)

### 3. Hidden "Ca học Hôm nay" (Lessons Today) Block
Temporarily hidden the "Ca học Hôm nay" card from the overview stats grid.
The block is commented out (not deleted) for easy re-enabling later.

## Files Modified
- `src/app/Models/User.php` - Updated `scopeLearners()` scope
- `src/resources/views/dashboard/index.blade.php` - Hidden buttons and blocks

## Notes
All changes are implemented as HTML comments, making them easy to restore when needed:
- Search for `<!-- HIDDEN: ... per Phase 8 -->` to find all hidden elements
- Simply remove the comment markers to restore functionality
