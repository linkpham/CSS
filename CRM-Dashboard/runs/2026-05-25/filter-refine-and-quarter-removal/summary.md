# Filter refine + quarter removal

Date: 2026-05-25
Production: `https://crm.icanwork.vn`

## Delivered
1. Added a note inside `Sức khỏe CSI` to clarify that CSI uses the shared filters.
2. Removed the `Quý` filter from the CRM root UI.
3. Added UI logic so when `Tháng` is selected:
   - `Từ ngày`
   - `Đến ngày`
   are automatically disabled and cleared to avoid overlapping time logic.
4. Made the filter layout more compact and denser for a cleaner look.

## UI changes
- new compact filter field styling
- filter grid changed to 5-column compact layout on desktop
- smaller labels and control height
- helper note under filters: `Khi đã chọn tháng, khoảng thời gian sẽ tự khóa để tránh chồng logic lọc.`
- `quarterFilter` removed from HTML

## Live verification
- root HTML contains:
  - `csiFilterNote`
  - `syncTimeFilterInputs`
  - compact `filter-field` styling
  - `Phạm vi & điều kiện lọc`
- root HTML no longer contains `quarterFilter`
- CSI checks still OK after UI refactor:
  - `month=2026-04` -> `5054`
  - `month=2026-04&targetCategory=Báo động` -> `347`
  - `fromDate=2026-04-01&toDate=2026-04-30` -> `5054`
