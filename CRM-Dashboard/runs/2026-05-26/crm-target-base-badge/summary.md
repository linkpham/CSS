# CRM target/base badge

Date: 2026-05-26
Production: `https://crm.icanwork.vn`

## Change
Giữ nguyên logic target/base hiện tại, chỉ bổ sung badge nhỏ ngay cạnh giá trị `Target / Base` để user nhìn nhanh được kỳ đang so sánh.

## Implementation
Updated `public/index.html`:
- added CSS `.basis-badge`
- added helpers:
  - `formatTargetBaseBadge(student)`
  - `renderTargetBaseValue(student)`
- applied badge to:
  - learner profile LCMS card `homeworkTargetBase`
  - student detail popup `studentDetailTargetBase`
  - homework-focus tables (`Target / Base` column)

## Basis shown
Badge format:
- `2026-04 vs 2026-03`
- or if no prior base: `2026-05 vs chưa có base`

## Verification
Live homepage markers present:
- `basis-badge`
- `renderTargetBaseValue`
- `formatTargetBaseBadge`
- `studentDetailTargetBase`
- `homeworkTargetBase`

Production student payload still includes:
- `targetSnapshotMonth`
- `baseSnapshotMonth`

Result: visual clarity improved without changing comparison logic.
