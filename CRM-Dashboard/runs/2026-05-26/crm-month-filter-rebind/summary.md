# CRM month-filter rebind fix

Date: 2026-05-26
Production app: `crm.icanwork.vn`

## User issue
User reported that month filter looked like it only worked for `2026-04`, while other months did not really run.

## Root cause
The unified CRM dataset was being built with a **default preferred CSI target month** (production default was `2026-04`) and most students were stamped with that target snapshot month.

So when filtering by month:
- `2026-04` looked correct because it matched the default target month for most rows
- other months only returned the much smaller subset of rows already stamped to those months

In other words, the time filter was filtering a pre-bound target month, instead of rebinding the target snapshot to the requested month.

## Fix applied
### Backend
Updated `src/app.js`:
- `buildMonthlySnapshotIndex(...)` now supports `targetMonthLabel`
- new `buildUnifiedDataPayload(targetMonthLabel)`
- new `resolveTimeScopedTargetMonth(filters)`
- `getScopedDashboardSource(...)` now rebuilds unified data against the requested month when:
  - `month` is selected
  - or `fromDate/toDate` resolve to a single month
- `getLearnerJourneySource(...)` now uses the same time-scoped dataset logic
- dashboard payload now exposes `compareBasis.activeTargetMonth`

### Resulting behavior
When user chooses month `YYYY-MM`:
- target snapshot is rebound to exactly that month
- base remains the nearest prior month with data for that learner
- month filter now behaves like a real target-month selector instead of only matching the old default snapshot month

## Remote verification on production container
Verified directly against app inside `icc-crm-app` after deploy:

```json
{"month":"2025-11","dashboard":92,"students":92,"activeTargetMonth":"2025-11"}
{"month":"2025-12","dashboard":299,"students":299,"activeTargetMonth":"2025-12"}
{"month":"2026-01","dashboard":5556,"students":5556,"activeTargetMonth":"2026-01"}
{"month":"2026-02","dashboard":5199,"students":5199,"activeTargetMonth":"2026-02"}
{"month":"2026-03","dashboard":5374,"students":5374,"activeTargetMonth":"2026-03"}
{"month":"2026-04","dashboard":5154,"students":5154,"activeTargetMonth":"2026-04"}
{"month":"2026-05","dashboard":4927,"students":4927,"activeTargetMonth":"2026-05"}
```

## Conclusion
Month filter is no longer effectively stuck on `2026-04`.
Each selected month now rebinds the CRM target snapshot correctly.
