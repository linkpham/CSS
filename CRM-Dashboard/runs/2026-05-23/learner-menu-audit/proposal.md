# Learner Menu Audit & Simplification Proposal

Date: 2026-05-23
Repo: `/mnt/f/Code/strongdm-main/CRM-Dashboard/CRM-Dashboard`
Primary file reviewed: `public/index.html`

## 1) Current learner-related top-level menus

Current `TAB_CONFIG` contains these learner-facing menus:
- `journey` — Learner Journey
- `health` — Sức khỏe học viên
- `live` — Buổi học live
- `homework` — Bài tập LCMS
- `packages` — Gói học & gia hạn
- `students` — Danh sách học viên
- `reports` — Báo cáo
- `analytics` — Zeus Analytics

Evidence: `public/index.html` around lines `2140-2147`.

## 2) Main overlap findings

### A. Journey / Live / Homework are 1 pattern split into 3 top-level tabs
All three tabs repeat the same UX skeleton:
- learner selector
- course group + class size filters
- universe table with pager
- summary cards for 1 selected learner
- progress blocks / notes

Evidence:
- Live section: `1679-1752`
- Homework section: `1755-1828`
- Journey section has the same structure earlier in file
- Shared renderer: `renderStudentUniverseTable(...)` and `renderWireframeLearner()`

Conclusion:
These are not three separate navigation destinations. They are three views of the same object: **1 learner / 1 cohort**.

### B. Filters are duplicated 4 times
The same learner scope filters appear in:
- Journey
- Live
- Homework
- Students

Evidence:
- repeated course/class filter containers
- constants `JOURNEY_COURSE_FILTER_CONTAINER_IDS` and `JOURNEY_CLASS_FILTER_CONTAINER_IDS`
- shared `clearJourneyFilters()` and `getJourneyFilters()` flow

Conclusion:
This should be a single shared **Learner Scope** control, not repeated per tab.

### C. Students already acts as the true worklist hub
Students tab already supports:
- search
- paging
- export
- preset/cohort mode
- drill-down/popup detail
- entry point from Live/LCMS/Analytics

Evidence:
- `openStudentsFocus(mode)` at `3550-3554`
- preset banner handling at `4040-4059`
- full students table at `4084+`

Conclusion:
`Students` is already the best candidate for the central action list / work queue.

### D. Reports is too thin to deserve a top-level menu
Reports currently contains only:
- priority students table
- quick snapshot table

Evidence:
- reports section around `1906-1911`

Conclusion:
This is duplicated by:
- Overview KPI + risk table
- Students export
- Zeus Analytics command views

`Reports` should be folded into Overview + Students + Analytics.

### E. Packages overlaps both Journey and Student Detail Modal
Packages tab contains:
- purchase history lookup
- forecast tables
- package & expiry table

But package-related detail is already visible in:
- Journey learner cards
- student detail modal purchase history
- Students table / Analytics / renewal views

Evidence:
- package history lookup section `1836-1848`
- modal purchase history renderer `3578-3604`
- package history loader `3614-3623`

Conclusion:
Packages currently mixes two jobs:
1. learner-level package history
2. portfolio-level renewal management

These should be split.

### F. Zeus Analytics and Reports partially overlap
Zeus Analytics now includes:
- renewal queues
- recovery queues
- activation bottlenecks
- class/package review
- action routing into Students

This is already the higher-value version of reporting.

Conclusion:
Top-level Reports is now largely redundant.

## 3) Recommended simplified IA

## Option recommended: reduce 7 learner menus to 4 core menus

### Keep as top-level
1. **Bàn điều hành**
   - KPI tổng
   - CSI health summary
   - coverage note
   - top risk snapshot

2. **Danh sách học viên**
   - becomes the main worklist / action hub
   - search, export, preset cohorts, popup detail
   - receives drill-down from all dashboards

3. **Hồ sơ học viên**
   - merge current Journey + Live + LCMS + learner-level package history
   - one selected learner
   - internal sub-tabs:
     - Tổng quan
     - Live
     - LCMS
     - Gói học

4. **Command Center**
   - rename current `Zeus Analytics`
   - keeps renewal / recovery / activation / product dashboards
   - every hotspot/lane opens Students cohort

### Remove as top-level
- `reports`
- `live`
- `homework`
- `packages` (as standalone top-level)

### Keep optional as separate top-level only if business still wants chart view
- `health`

If kept, `health` should be clearly positioned as:
- **CSI Health Monitor**
- cohort health chart view only
- not a place for drill-down workflows

If removed as top-level, move it into Overview as a section.

## 4) Proposed menu structure after simplification

### Preferred structure
- Bàn điều hành
- CSI Health (optional)
- Danh sách học viên
- Hồ sơ học viên
- Command Center
- Ticket
- Users
- Tiện ích
- Tài khoản

### If we want maximum simplification
- Bàn điều hành
- Danh sách học viên
- Hồ sơ học viên
- Command Center
- Ticket
- Users
- Tiện ích
- Tài khoản

## 5) Function-level merge recommendations

### A. Merge Journey + Live + LCMS + learner package history into 1 learner profile
Current issue:
- user must switch tabs to understand 1 learner fully

New model:
- top-level tab: **Hồ sơ học viên**
- internal view switcher:
  - Tổng quan
  - Live
  - LCMS
  - Gói học

Result:
- less menu clutter
- one place to understand one learner
- less duplicated filter/list/selection UI

### B. Make Students the single “worklist” destination
Current issue:
- `Đi tới danh sách` appears in Live, LCMS, Analytics
- user conceptually already lands on Students for action

Decision:
- standardize that all cohort/risk/action routing ends in Students
- Students owns:
  - list management
  - export
  - preset banner
  - bulk review flow

### C. Move package history out of a standalone tab mentality
Current issue:
- package history is learner-specific but exposed as a top-level menu block

Decision:
- primary access via Student Detail modal and Hồ sơ học viên > Gói học
- portfolio renewal tables move to Command Center / Overview

### D. Fold Reports into other areas
Move:
- `Ưu tiên can thiệp` -> Command Center or Overview
- `Báo cáo nhanh` -> Overview snapshot panel
- export stays in Students

### E. Keep one shared learner scope bar
Shared controls should live once, not 4 times:
- course group
- class size
- search
- CSS
- date / CSI period where relevant

Suggested behavior:
- top shared learner scope applies to Students, Hồ sơ học viên, Command Center
- Health may use a reduced scope if needed

## 6) Wording / naming recommendations

### Rename tabs
- `Learner Journey` -> `Hồ sơ học viên`
- `Zeus Analytics` -> `Command Center`
- `Bài tập LCMS` -> internal sub-tab `LCMS`
- `Buổi học live` -> internal sub-tab `Live`
- `Gói học & gia hạn` -> internal sub-tab `Gói học` or `Gia hạn`
- `Danh sách học viên` can stay, or rename to `Worklist học viên`

### Rename actions
Standardize row actions to 3 labels only:
- `Mở hồ sơ`
- `Xem popup`
- `Mở danh sách`

Avoid mixing:
- Journey
- Live
- LCMS
- Xem chi tiết
- Đi tới danh sách

unless truly necessary.

## 7) Low-risk implementation order

### Phase 1 — no major logic rewrite
1. Remove top-level `Reports`
2. Rename `Zeus Analytics` -> `Command Center`
3. Rename `Learner Journey` -> `Hồ sơ học viên`
4. Keep `live`, `homework`, `packages` temporarily but relabel as secondary/internal or hide from main nav
5. Move report content into Overview/Command Center

### Phase 2 — actual IA consolidation
1. Create one top-level `Hồ sơ học viên`
2. Move current Journey/Live/Homework/Package blocks into internal sub-tabs inside that page
3. Remove top-level `live`, `homework`, `packages`
4. Keep Students as central worklist and Command Center as action dashboard

### Phase 3 — polish
1. One shared learner scope bar
2. One unified row action model
3. Add saved cohorts / saved views in Students
4. Optional: click directly on matrix cells in Command Center

## 8) Recommendation summary

If choosing the best business-friendly simplification now:
- **Keep**: Overview, Students, Hồ sơ học viên, Command Center
- **Optional keep**: CSI Health
- **Remove from top nav**: Live, LCMS, Packages, Reports
- **Use Students as worklist hub**
- **Use Hồ sơ học viên as 360 learner page**
- **Use Command Center as action dashboard**

This is the cleanest structure with the least conceptual overlap.
