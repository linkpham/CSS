# Ticket student drilldown and risk insights

Date: 2026-05-26
Module: `https://crm.icanwork.vn/ticket/`

## Delivered
Implemented both requested upgrades:
1. drill-down from learner comparison blocks into the ticket list for a specific learner
2. richer learner-risk insights in the comparison area

## Drill-down by learner
### Backend
Updated `src/services/analyticsService.js`:
- `applyFilters(...)` now supports `studentKey`
- `serializeTicket(...)` now returns:
  - `studentKey`
  - `studentLabel`

### Frontend
Updated `public/index.html`:
- added drill-down helpers:
  - `openStudentTicketDrilldown(...)`
  - `clearStudentTicketDrilldown()`
  - `getActiveTicketFilters()`
- added banner:
  - `ticketDrilldownBanner`
- drill-down list now shows learner column
- repeat / new-2026 / risk learner tables now include action buttons to open that learner's tickets

### Production verification
- page HTML contains:
  - `ticketDrilldownBanner`
  - `openStudentTicketDrilldown`
- API check:
  - `GET /ticket/api/tickets?download=all&studentKey=st35835` returns `8` tickets

## Learner risk insights
### Backend
Expanded `yearComparison.studentComparison` with:
- `riskRows`
- `riskScore`
- `riskReason`

### Frontend
Added section:
- `Học viên risk cao nhất`

Also kept/enhanced:
- `So sánh theo học viên`
- `Học viên lặp lại qua 2 năm`
- `Học viên mới nổi ở 2026`

### Production verification
- HTML contains:
  - `Học viên risk cao nhất`
  - `studentRiskBody`
- API returns:
  - `riskRows`
  - `repeatedRows`
  - `new2026Rows`

## Current production snapshot
- `riskRows = 8`
- `repeatedRows = 8`
- `new2026Rows = 8`
- example repeated learner key: `st35835`
- student drill-down filter returns correct scoped tickets for that learner
