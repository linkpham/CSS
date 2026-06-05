# Ticket student comparison

Date: 2026-05-25
Module: `https://crm.icanwork.vn/ticket/`

## Delivered
Added a new student-level comparison block to the Ticket module so users can review issue volume and compensation impact by learner between:
- `2025 · SpeakWell`
- `2026 · Combined`

## Backend changes
Updated:
- `src/services/analyticsService.js`

Added logic for:
- learner identity stitching using priority:
  1. `studentCode`
  2. `studentName`
  3. `aiStudent`
  4. `requesterName`
- student-level coverage stats by year
- student overlap count across 2025 and 2026
- top learner comparison rows with:
  - `count2025`
  - `count2026`
  - `deltaCount`
  - `sessions2025`
  - `sessions2026`
  - `deltaSessions`

## Frontend changes
Updated:
- `public/index.html`

Added a new section:
- `So sánh theo học viên`

UI elements:
- `studentComparisonNote`
- `studentCompareCoverage`
- `studentCompareBody`

## Production verification
Verified on production:
- HTML contains student comparison section markers
- API returns `yearComparison.studentComparison`
- sync status remains `completed`
- total rows remain `4740`

### Production snapshot
- 2025 coverage:
  - total tickets: `2093`
  - matched tickets: `2093`
  - matched rate: `100%`
  - unique students: `1695`
- 2026 coverage:
  - total tickets: `2647`
  - matched tickets: `1336`
  - matched rate: `50.5%`
  - unique students: `1193`
- shared students across both years: `37`

## Important note
Student comparison is best-effort, because many ticket rows do not contain a clean learner identity. The section explicitly notes that matching uses `studentCode` first, then falls back to `studentName / AI student / requester`.