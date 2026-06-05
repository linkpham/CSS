# CRM comment batch final verification

Date: 2026-05-25
App: `https://crm.icanwork.vn`

## Scope closed
Final verification for the latest CRM comment batch, including:
- month filter missing learners
- renewal/product filtering consistency
- teacher type filter usability
- CSI lag mitigation
- learner-profile/worklist action flow
- LCMS proxy wording clarity

## Production verification
### Population + month coverage
- `GET /api/students?download=all`
  - total students: `6408`
  - missing month: `0`

### Month filter consistency
For `month=2026-05`:
- dashboard rowCount: `270`
- students total: `270`
- learner journey total: `270`

=> month filter is aligned across dashboard / students / learner journey.

### Product type filter
Verified production returns usable values:
- `Easy Speak for Adults`
- `SpeakWell Get Ready`
- `SpeakWell Hero`
- `SpeakWell for Teens`

Example check:
- `month=2026-05&productType=SpeakWell Hero` -> `167` students

### Teacher type filter
Teacher type is now populated from Zeus teacher country via learner-journey sync, then normalized to business-friendly buckets:
- `GV Việt Nam`
- `GV Philippines`
- `GV Native`
- `Mixed teacher types`

Production counts after sync:
- `GV Việt Nam`: `3037`
- `GV Philippines`: `3009`
- `GV Native`: `133`
- `Mixed teacher types`: `229`

Example check:
- `month=2026-05&teacherType=GV Việt Nam` -> `123` students
- `month=2026-05&teacherType=Mixed teacher types` -> `7` students

### CSI lag mitigation
Production timing check for `/api/csi/health-dashboard`:
- first request: ~`4.1s`
- repeated request: ~`0.2s`

=> backend cache is working as intended.

## IA conclusion for learner profile vs student list
Reviewed comment about merging learner profile with student list.
Decision for this batch: **keep the current low-risk pattern**
- `Danh sách học viên` remains the worklist hub
- `Hồ sơ học viên` remains the deep-dive workspace
- action button from worklist opens directly into learner profile

This is considered sufficient for the current comment batch without taking on a riskier IA rewrite.

## Status
Comment batch is considered **completed** for current scope.
