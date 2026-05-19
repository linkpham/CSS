# Phase 10 - Fix SQL Tooltips Consistency in Acceptance Code Section

## ✅ Completed Features

### Issue Identified
The `📊 Thống kê theo mã Acceptance Code` section was displaying inconsistent SQL queries in tooltips compared to the `📊 Thống kê Ca học` section. 

In the Session Stats section, the "Tổng số ca hoàn thành" (Total Completed Sessions) uses this SQL:
```sql
SELECT COUNT(*) FROM tbl_order_lessons
WHERE ordles_tlang_id IN (533, 558, 560, 562, 580, 581, 564, 567, 568, 569, 416, 415, 414, 413, 571, 572, 574, 575, 576, 389, 390, 392, 405, 406, 407, 411, 412, 577, 586, 585, 584, 582, 404, 403, 583, 471)
AND ordles_status = 3
AND ordles_lesson_starttime BETWEEN [start] AND [end]
```

However, the Acceptance Code section's SQL tooltips were **missing the `ordles_tlang_id IN (...)` filter** in several periods (yesterday, day before yesterday, week, month).

### Fix Applied
Updated all SQL tooltips in the `📊 Thống kê theo mã Acceptance Code` section to include the SPEAKWELL_SUBJECT_IDS filter consistently:

**Periods Fixed:**
1. **Today** - Fixed "Thành công (Code 12)" and "Không thành công" tooltips
2. **Yesterday** - Fixed all 3 tooltips (Tổng hoàn thành, Thành công, Không thành công)
3. **Day Before Yesterday (Hôm kia)** - Fixed all 3 tooltips
4. **Week Stats (Tuần này)** - Fixed all 3 tooltips
5. **Month Stats (Tháng này)** - Fixed all 3 tooltips

### SQL Queries Updated
Each tooltip now correctly includes the subject ID filter:

**Tổng hoàn thành:**
```sql
SELECT COUNT(*) FROM tbl_order_lessons 
WHERE DATE(ordles_lesson_starttime) = [date] 
AND ordles_status = 3 
AND ordles_tlang_id IN (533,558,560,...)
```

**Thành công (Code 12):**
```sql
SELECT COUNT(*) FROM tbl_order_lessons ol 
JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id 
WHERE DATE(ol.ordles_lesson_starttime) = [date] 
AND ol.ordles_status = 3 
AND ol.ordles_tlang_id IN (533,558,560,...) 
AND ole.ole_acceptance_code = 12
```

**Không thành công:**
```sql
SELECT COUNT(*) FROM tbl_order_lessons ol 
JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id 
WHERE DATE(ol.ordles_lesson_starttime) = [date] 
AND ol.ordles_status = 3 
AND ol.ordles_tlang_id IN (533,558,560,...) 
AND ole.ole_acceptance_code != 12
```

### Files Modified
- `src/resources/views/dashboard/index.blade.php` - Updated SQL tooltip queries in the Acceptance Code Statistics section

## Notes
- The backend code (`DashboardService.php`) already correctly uses the `SPEAKWELL_SUBJECT_IDS` filter
- This fix was purely cosmetic - updating the tooltip documentation to match the actual SQL being executed
- All tooltips now accurately reflect the queries used to calculate the displayed metrics
