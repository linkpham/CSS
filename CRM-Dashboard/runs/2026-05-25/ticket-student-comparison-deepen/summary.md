# Ticket student comparison deepen

Date: 2026-05-25
Module: `https://crm.icanwork.vn/ticket/`

## Added on top of the first student comparison release
Expanded learner-level analysis with more action-oriented sections:
- `So sánh theo học viên`
- `Học viên lặp lại qua 2 năm`
- `Học viên mới nổi ở 2026`

## Backend
Updated `src/services/analyticsService.js` to return in `yearComparison.studentComparison`:
- `coverage2025`
- `coverage2026`
- `sharedStudents`
- `rows`
- `repeatedRows`
- `new2026Rows`

## Frontend
Updated `public/index.html` to render:
- `studentCompareCoverage`
- `studentCompareBody`
- `studentRepeatBody`
- `studentNew2026Body`

## Production verification
- HTML markers verified live
- API returns:
  - `repeatedRows`
  - `new2026Rows`
- sync status remains `completed`
- total rows remain `4740`

## Current production snapshot
- repeatedRows count: `8`
- new2026Rows count: `8`
- shared students across 2025 and 2026: `37`

## Important note
The learner-level ticket comparison is still best-effort because 2026 has incomplete learner identity coverage; matching prioritizes `studentCode`, then falls back to `studentName / aiStudent / requesterName`.
