# CRM student-status filter + time-filter audit

Date: 2026-05-26
Production: `https://crm.icanwork.vn`

## Objective
- Bổ sung bộ lọc trạng thái học viên `Active / Expired`
- Rà lại logic bộ lọc thời gian `Tháng / Từ ngày / Đến ngày`
- Làm rõ căn cứ so sánh `target / base` khi user không chọn bộ lọc thời gian

## Changes made
### 1) New filter: Trạng thái học viên
Added new global filter:
- `studentStatusFilter`
- values from backend filter options: `Active`, `Expired`

Backend:
- `src/app.js`
  - added `deriveStudentStatus(...)`
  - populate `renewal.studentStatus`
  - serialize top-level `studentStatus`
  - support `studentStatus` in learner-journey filtering path
- `src/services/analyticsService.js`
  - support `studentStatus` in `applyFilters(...)`
  - include `studentStatus` in `getFilterOptions(...)`

Frontend:
- `public/index.html`
  - added filter field `Trạng thái học viên`
  - included in `hydrateFilters()`, `getFilters()`, filter summary chip text

### 2) Time-filter note clarified
Updated filter behavior note so user can understand:
- `Sức khỏe kỳ này / kỳ trước` only filter by score categories
- they do **not** choose target/base time periods
- target/base time basis is now explained explicitly depending on:
  - no time filter
  - month filter
  - date-range filter

### 3) Target/base basis surfaced in student-facing UI
Added fields and display basis:
- `targetSnapshotMonth`
- `baseSnapshotMonth`
- helper `formatTargetBaseBasis(student)`

Now shown in:
- learner profile target/base notes
- LCMS / study notes
- student detail popup LCMS meta

### 4) Dashboard payload compare basis
Added:
- `compareBasis.defaultTargetMonth`

This lets frontend explain the default comparison month when no time filter is selected.

## Audit findings
### Default target/base basis when no time filter is selected
Current production dashboard now exposes:
- `compareBasis.defaultTargetMonth = 2026-04`

Meaning:
- if user does **not** choose `Tháng` or `Từ ngày / Đến ngày`
- CRM currently defaults target comparison to snapshot month `2026-04`
- base is the nearest prior month with data for each learner

### Time-filter behavior check
Production API test:
- `/api/students?month=2026-05` -> `280`
- `/api/students?fromDate=2026-05-01&toDate=2026-05-31` -> `280`
- result: equal totals => month/date-range mapping is consistent for this test case

## Production verification
### Dashboard filter options
`/api/dashboard`
- `filterOptions.studentStatus = ['Active', 'Expired']`
- `compareBasis.defaultTargetMonth = '2026-04'`

### Student-status filter tests
`/api/students?studentStatus=Active`
- total = `5119`
- sample rows all `studentStatus = Active`

`/api/students?studentStatus=Expired`
- total = `1229`
- sample rows all `studentStatus = Expired`

### Month-scoped student payload test
`/api/students?month=2026-05&pageSize=3`
- rows include `targetSnapshotMonth`
- rows include `baseSnapshotMonth`
- so frontend can now show the basis of target/base comparison

### HTML markers verified live
Homepage HTML contains:
- `studentStatusFilter`
- `Trạng thái học viên`
- `Hai bộ lọc “Sức khỏe kỳ này / kỳ trước” chỉ lọc theo nhóm điểm.`
- `formatTargetBaseBasis`
- `targetSnapshotMonth`
- `baseSnapshotMonth`

## Result
- Added requested `Active / Expired` learner-status filter
- Time-filter messaging is clearer
- Target/base comparison basis is now explicit instead of hidden
- Production tested for filter options, status filtering, and month/date consistency
