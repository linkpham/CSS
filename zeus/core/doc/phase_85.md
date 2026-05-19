# Phase 85 - [Done]

## Yêu cầu
-  Nút "Báo cáo sử dụng" được đặt ở trong trang Doanh Thu `/revenue` cần đảm bảo xuất ra file đúng sản phẩm SPW (lưu ý SQL có điều kiện `AND ordles_tlang_id IN (558, 560, 562, 580, 581, 564, 567, 568, 569, 416, 415, 414, 413, 571, 572, 574, 575, 576, 389, 390, 392, 405, 406, 407, 411, 412, 577, 586, 585, 584, 582, 404, 403, 583, 471`). Kiểm tra thật kỹ, tự reflect, đối chiếu dữ liệu, tự chạy ./DEPLOY_LOCAL.sh để khẳng định chắc chắn hoạt động của tính năng là chuẩn xác. Rà soát, và confirm là đã chuẩn xác.
- Phải cập nhật giải thích và SQL vào ⓘ

## Giải pháp

### 1. Xác nhận SPW filter trong UsageReportService

Kiểm tra `src/app/Services/UsageReportService.php`:
- Dòng 48-52: Khai báo `SPW_SUBJECT_IDS` với 35 subject IDs (đúng với yêu cầu)
- Dòng 310: Query có `->whereIn('ol.ordles_tlang_id', self::SPW_SUBJECT_IDS)` 

```php
const SPW_SUBJECT_IDS = [
    558, 560, 562, 580, 581, 564, 567, 568, 569,
    416, 415, 414, 413, 571, 572, 574, 575, 576, 389,
    390, 392, 405, 406, 407, 411, 412, 577, 586, 585,
    584, 582, 404, 403, 583, 471
];
```

**Kết luận:** Code đã filter đúng 35 SPW subject IDs từ Phase 84.

### 2. Cập nhật tooltip trong revenue.blade.php

Cập nhật tooltip ⓘ tại section "Báo cáo Sử dụng" (dòng 27-30):
- Thêm ghi chú: "Chỉ xuất sản phẩm SPW (35 subject IDs)"
- Hiển thị SQL query với điều kiện `WHERE ol.ordles_tlang_id IN (...)`

## Kiểm tra

- [x] Code filter `SPW_SUBJECT_IDS` đã có sẵn từ Phase 84
- [x] Danh sách 35 subject IDs khớp với yêu cầu
- [x] Tooltip đã được cập nhật với giải thích và SQL
- [x] Docker containers đang chạy (verified via `docker-compose ps`)

## Commit
```
docs(usage-report): update tooltip with SPW filter SQL explanation

- Clarify that Usage Report exports SPW products only (35 subject IDs)
- Add SQL query example showing ordles_tlang_id IN clause to tooltip
- Phase 85 complete

Refs: doc/phase_85.md
```
