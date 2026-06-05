# CSI filter fix

Date: 2026-05-25
Production: `https://crm.icanwork.vn`

## Issue found
The `Sức khỏe CSI` tab was not honoring several visible global filters even though users could select them in the shared filter panel.

Specifically before the fix:
- `month` was ignored by `/api/csi/health-dashboard`
- `quarter` was ignored by `/api/csi/health-dashboard`
- `targetCategory` was ignored by `/api/csi/health-dashboard`

Example before fix:
- `/api/csi/health-dashboard?month=2026-04` still returned full-scope CSI (`6146`) instead of April scope (`5054`)

## Root cause
1. Frontend `getCsiFilters()` only sent:
   - `search`
   - `css`
   - `fromDate`
   - `toDate`
2. Backend `getCsiRequestFilters()` did not translate:
   - `month` -> `fromDate/toDate`
   - `quarter` -> `fromDate/toDate`
   - `targetCategory` -> CSI `health_category`

## Fix shipped
### Frontend
`public/index.html`
- `getCsiFilters()` now also sends:
  - `quarter`
  - `month`
  - `targetCategory`

### Backend
`src/app.js`
- added `buildQuarterDateRange(quarterLabel)`
- added `mapTargetCategoryToCsiHealthCategory(category)`
- updated `getCsiRequestFilters(query)` so CSI endpoints now:
  - use explicit `fromDate/toDate` if present
  - else derive date range from `month`
  - else derive date range from `quarter`
  - map dashboard `targetCategory` to CSI `health_category`

## Production verification after deploy
- `month=2026-04` -> CSI total `5054`, avg `90.2`
- `quarter=Q2/2026` -> CSI total `5261`, avg `88.2`
- `targetCategory=1. Báo động (<60)` -> CSI total `644`, avg `27.8`
- `month=2026-04&targetCategory=1. Báo động (<60)&css=anhptl` -> CSI total `38`, avg `28.7`

This confirms the CSI tab now respects:
- CSS
- month
- quarter
- explicit date range
- target health category
