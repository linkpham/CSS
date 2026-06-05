# CRM filter UI note rollout

Date: 2026-05-25
Production: `https://crm.icanwork.vn`

## Changes
Added a short in-UI clarification under `Bộ lọc nâng cao`:
- time filters are interpreted by CRM snapshot/month period
- choosing any day inside a month still maps to that whole month snapshot
- learners without coverage in that selected period may not appear
- renewal status is read against the selected period

Also improved the active filter summary text so that when only `Từ ngày / Đến ngày` is used, the header now shows:
- `Kỳ ngày ... -> ...`

## Production verification
HTML verified for:
- `filterBehaviorNote`
- `updateFilterBehaviorNote`
- snapshot/month explanation text

## Extra consistency checks
- `quarter=Q2/2026` == `fromDate=2026-04-01&toDate=2026-05-31`
  - both returned `5261` rows
- `quarter=Q2/2026&css=anhptl` returned `605` rows
- `month=2026-04&css=anhptl` returned `600` rows
- `quarter=Q1/2026&renewalStatus=✅ Đã gia hạn` returned `118` rows

These checks support that quarter/month/date + CSS/renewal combinations remain coherent after the earlier fix.
