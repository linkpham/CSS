# CSI final audit and REQUESTS consolidation

Date: 2026-05-26
Production: `https://crm.icanwork.vn`

## Scope
1. Rà bước cuối cho tab CSI live
2. Consolidate lại `REQUESTS.md` theo các step đã hoàn thành và các step còn mở

## Findings from CSI audit
### 1) CSI live trước đó chưa siết role scope thật
Trước khi fix, test trực tiếp trong production container cho thấy:
- Head `month=2026-05` → `4850` học viên CSI
- Staff `anhptl` `month=2026-05` cũng ra `4850`

=> Tab CSI live chưa bám role scope backend, chỉ dựa frontend filters nên có risk lộ scope rộng hơn mong muốn.

### 2) CSI live chưa nhận `studentStatus`
Bộ lọc `Active / Expired` đã áp dụng ở dashboard / students / learner views, nhưng tab CSI live chưa wire cùng logic này.

## Fix applied
### Backend
Updated `src/app.js`
- đổi `getCsiRequestFilters(...)` thành async và nhận `user`
- tự map `month` sang `fromDate/toDate` như cũ
- bổ sung role scope backend cho CSI bằng `getAllowedCssScopes(user)`
- khi có `studentStatus`, derive `student_ids` từ unified scoped dataset bằng `getScopedDashboardSource(...)`
- cập nhật toàn bộ endpoints CSI để dùng `await getCsiRequestFilters(req.query, req.user)`

Updated `src/services/csiService.js`
- hỗ trợ `css_scopes`
- hỗ trợ `student_ids`
- `buildCsiWhereClause(...)` nay có thể filter theo:
  - `css_staff = ?`
  - `css_staff IN (...)`
  - `student_id IN (...)`

### Frontend
Updated `public/index.html`
- `getCsiFilters()` nay gửi thêm `studentStatus`

## Production verification
### Role scope fixed
Verified trực tiếp trong `icc-crm-app`:
```json
{
  "headMay": 4850,
  "staffMay": 552,
  "staffExpired": 31,
  "staffActive": 521
}
```

Ý nghĩa:
- Head vẫn thấy toàn bộ CSI scope tháng 5
- Staff `anhptl` chỉ còn thấy đúng scope của mình
- CSI live nay tôn trọng `Active / Expired`

### Returned filter payload
Sample `staffFilters` sau fix:
```json
{
  "css_scopes": ["anhptl"],
  "date_from": "2026-05-01",
  "date_to": "2026-05-31"
}
```

Sample `staffExpiredFilters` sau fix có thêm `student_ids` đã derive từ scoped unified dataset.

### Time filter still correct
Verified:
- `/api/csi/health-dashboard?month=2026-05`
- `/api/csi/health-dashboard?fromDate=2026-05-01&toDate=2026-05-31`

Result:
- both return `4850`
- same-month range still matches month filter

## REQUESTS.md consolidation
Updated 3 files:
- `/mnt/f/Code/strongdm-main/REQUESTS.md`
- `/mnt/f/Code/strongdm-main/template/REQUESTS.md`
- `/mnt/f/Code/strongdm-main/CRM-Dashboard/CRM-Dashboard/template/REQUESTS.md`

New structure is step-based and now reflects:
- CRM root + Ticket submodule architecture
- Zeus / LCMS / learner universe milestones
- filter/time/status milestones
- CSI final scope hardening milestone
- remaining next steps kept concise

## Conclusion
Final CSI audit completed:
- CSI live now respects backend role scope
- CSI live now supports `studentStatus`
- time filter remains consistent

`REQUESTS.md` has been consolidated to match the real project state and current step roadmap.
