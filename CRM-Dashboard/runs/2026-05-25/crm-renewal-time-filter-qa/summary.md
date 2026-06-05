# CRM renewal/time filter QA

Date: 2026-05-25
Environment: production `https://crm.icanwork.vn`

## Scope checked
- Filter `Trạng thái gia hạn`
- Filters `Từ ngày` / `Đến ngày`
- Consistency across:
  - `/api/dashboard`
  - `/api/students?download=all`
  - `/api/learner-journey/students`

## Fixes already deployed before QA
1. Renewal status is now derived against each row's `period.month`, not one fixed global target month.
2. Date filtering now behaves as month-snapshot filtering for CRM data.
3. Reversed date input (`fromDate > toDate`) is normalized automatically.
4. Learner universe endpoints now receive the same dashboard filters, so Students / Hồ sơ học viên stay aligned.

## QA results
### A. Month-level totals are now consistent
| Month | Dashboard | Students | Learner journey |
|---|---:|---:|---:|
| 2025-12 | 5 | 5 | 5 |
| 2026-01 | 231 | 231 | 231 |
| 2026-02 | 185 | 185 | 185 |
| 2026-03 | 464 | 464 | 464 |
| 2026-04 | 5054 | 5054 | 5054 |
| 2026-05 | 207 | 207 | 207 |

### B. Month + renewal-status combinations all matched
Checked all 24 combinations:
- months: `2025-12`, `2026-01`, `2026-02`, `2026-03`, `2026-04`, `2026-05`
- statuses:
  - `✅ Đã gia hạn`
  - `Bán mới`
  - `⏳ Chưa đến hạn`
  - `⏳ Chưa gia hạn`

Result:
- **0 mismatches** between dashboard / students / learner journey.

### C. Date-range checks passed
| Date filter | Expected behavior | Dashboard | Students | Learner journey |
|---|---|---:|---:|---:|
| `2026-04-01 → 2026-04-30` | same as month `2026-04` | 5054 | 5054 | 5054 |
| `2026-04-15 → 2026-04-15` | still maps to snapshot month `2026-04` | 5054 | 5054 | 5054 |
| `2026-05-01 → 2026-05-31` | same as month `2026-05` | 207 | 207 | 207 |
| `2026-05-31 → 2026-05-01` | reversed input normalized | 207 | 207 | 207 |
| `2026-04-01 → 2026-05-31` | combined April + May | 5261 | 5261 | 5261 |

## Business note
- Total CRM population currently: `6400`
- Population with month-tagged CSI snapshot rows across listed months: `6146`
- Difference: `254` learners without CSI month coverage in current snapshot logic.

Implication:
- When user applies month/date filter, these `254` rows are excluded.
- This is expected under current rule because time filters operate on snapshot month coverage, not on all journey-only rows.

## Current recommendation
No further code fix is needed for the 2 reported filter issues.
If needed later, the next improvement would be a small UI note explaining that month/date filters apply to the CSI snapshot period and may exclude learners without period coverage.
