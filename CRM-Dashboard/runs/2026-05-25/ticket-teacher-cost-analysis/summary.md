# Ticket teacher-cost relevance analysis

Date: 2026-05-25
Production module: `https://crm.icanwork.vn/ticket/`

## Data check
- Ticket rows on production dashboard: `949`
- Focus area counts:
  - Tech: `400`
  - Teacher: `280`

## Added analysis dimension
For Tech and Teacher focus areas, ticket logic now derives a new field:
- `teacherCostImpact`
- `teacherCostImpactKey`
- `teacherCostConfidence`

The classification is heuristic from ticket text (`topic`, `feedbackSummary`, `noteText`, `aiSummary`, `aiActions`, `sourceComments`):
- `Có dấu hiệu không trả chi phí GV`
  - explicit signals like `không tính lương`, `không trả lương`, `không tính công`, `phạt giáo viên`, `dừng hợp tác`
- `Có dấu hiệu vẫn phát sinh chi phí GV`
  - explicit pay signals like `được tính lương`, `vẫn tính lương`
  - or presence/teaching signals like `vào lớp đúng giờ`, `ở trong lớp`, `đã dạy hết bài`, `monitor ghi nhận GV`
- `Cần đối soát thêm chi phí GV`
  - no explicit signal strong enough in ticket text

## Production numbers after deploy
### Tech
- Total focus rows: `400`
- Compensation tickets: `397`
- Compensation sessions: `429`
- Teacher-cost split:
  - `Có dấu hiệu không trả chi phí GV`: `5` tickets / `4` sessions
  - `Có dấu hiệu vẫn phát sinh chi phí GV`: `5` tickets / `5` sessions
  - `Cần đối soát thêm chi phí GV`: `390` tickets / `420` sessions

### Teacher
- Total focus rows: `280`
- Compensation tickets: `277`
- Compensation sessions: `685`
- Teacher-cost split:
  - `Có dấu hiệu không trả chi phí GV`: `32` tickets / `60` sessions
  - `Có dấu hiệu vẫn phát sinh chi phí GV`: `22` tickets / `33` sessions
  - `Cần đối soát thêm chi phí GV`: `226` tickets / `592` sessions

## UI changes deployed
- New section in Tech: `Chi phí GV trong issue kỹ thuật`
- New section in Teacher: `Chi phí GV trong issue giáo viên`
- New review tables for cases still needing teacher-cost reconciliation
- Drill-down issue list now includes a `Chi phí GV` column

## Important note
This dimension is **decision-support**, not final accounting truth.
It helps operations see where teacher-cost handling is:
- explicitly waived
- still likely incurred
- or still ambiguous and needs manual reconciliation.
