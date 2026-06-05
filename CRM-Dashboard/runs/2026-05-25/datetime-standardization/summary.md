# Datetime standardization across CRM

Date: 2026-05-25
Production: `https://crm.icanwork.vn`

## Goal
Standardize the display of key milestone timestamps across CRM:
- first lesson
- latest lesson
- latest order / renewal date
- last sync

## Change
Added helper:
- `formatDateTimeValue(value)`

Format used:
- `dd/mm/yyyy, HH:mm` via `toLocaleString('vi-VN', { year, month, day, hour, minute })`

## Applied to
- learner detail popup
- learner profile summary cards / notes
- students worklist first-lesson column
- purchase history table date column
- package focus table latest order date
- scope summary `Lần sync gần nhất`
- activity feed `Sync gần nhất`
- top status line `Sync: ...`

## Production verification
Verified root HTML contains:
- `function formatDateTimeValue`
- `studentDetailLessonTimeline`
- `journeyFirstLessonMeta`
- `formatDateTimeValue(data.lastSyncedAt)`

This confirms the new standardized datetime formatting is live.
