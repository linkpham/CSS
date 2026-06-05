# CRM date-range audit and fix

Date: 2026-05-26
Production: `https://crm.icanwork.vn`

## Scope
Rà lại bộ lọc `Từ ngày / Đến ngày` sau khi đã sửa month filter.

## Findings
### 1) Multi-month date range vẫn chưa đúng hoàn toàn
Khi chọn range nhiều tháng, unified CRM data vẫn đang fallback về snapshot target mặc định của CRM cho nhiều học viên (đặc biệt `2026-04`), thay vì chọn snapshot tháng phù hợp nằm trong khoảng ngày đã chọn.

Ví dụ trước khi fix:
- học viên `1746` với range `2026-01-01 -> 2026-05-31` vẫn ra `targetSnapshotMonth = 2026-04`
- nhưng nếu lọc riêng `month=2026-05` thì cùng học viên đó có `targetSnapshotMonth = 2026-05`

### 2) Base month đang lấy sai hướng
Trong `buildMonthlySnapshotIndex(...)`, `baseSnapshot` trước đó lấy snapshot đầu tiên ở quá khứ thay vì snapshot gần nhất trước target.
Điều này làm một số ca ra base kiểu `2026-01` dù target là `2026-05` và thực tế có dữ liệu `2026-04`.

## Fix applied
Updated `src/app.js`:
- `buildMonthlySnapshotIndex(...)`
  - hỗ trợ `fromMonthLabel` / `toMonthLabel`
  - nếu lọc range nhiều tháng, chọn **snapshot tháng mới nhất trong khoảng** cho từng học viên
  - sửa `baseSnapshot` thành **tháng gần nhất trước target** (`reverse().find(...)`)
- thêm `normalizeTimeScope(scope)`
- thay `resolveTimeScopedTargetMonth(...)` bằng `resolveTimeScopedMonthWindow(...)`
- `buildUnifiedDataPayload(...)` nhận cả single-month và month-window scope
- `getScopedDashboardSource(...)` và `getLearnerJourneySource(...)` cùng rebind dữ liệu theo range thực tế
- `compareBasis` trả thêm:
  - `activeTargetRange.fromMonth`
  - `activeTargetRange.toMonth`
  - `activeTargetRange.mode`

Updated `public/index.html`:
- note UI range được sửa rõ hơn:
  - `kỳ target là snapshot tháng mới nhất của từng học viên nằm trong khoảng`
  - `kỳ base là tháng gần trước có dữ liệu`

## Production verification
### Same-month date range == month filter
```json
{"label":"2026-04","monthTotal":5154,"rangeTotal":5154,"equal":true}
{"label":"2026-05","monthTotal":4930,"rangeTotal":4930,"equal":true}
```

### Multi-month range now binds correctly
Sample learner checks after deploy:
```json
{"name":"month-2026-05","sample":[{"id":"1746","target":"2026-05","base":"2026-04"}]}
{"name":"range-jan-may","sample":[{"id":"1746","target":"2026-05","base":"2026-04"},{"id":"3080","target":"2026-03","base":"2026-02"}]}
{"name":"range-mar-may","sample":[{"id":"1746","target":"2026-05","base":"2026-04"},{"id":"3080","target":"2026-03","base":"2026-02"}]}
```

### Dashboard compare basis for range
```json
{"defaultTargetMonth":"2026-04","activeTargetMonth":"","activeTargetRange":{"fromMonth":"2026-01","toMonth":"2026-05","mode":"range"}}
```

## Conclusion
- `month` filter: đã chạy đúng
- `same-month date range`: đã khớp 1-1 với month filter
- `multi-month date range`: giờ chọn **target month mới nhất trong khoảng** cho từng học viên
- `base month`: giờ là **tháng gần nhất trước target**, không còn nhảy ngược về tháng quá xa
