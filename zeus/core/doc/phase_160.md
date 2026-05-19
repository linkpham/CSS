# Phase 160 - Revert CSI to Phase 154 State

## Vấn đề
- Kể từ Phase 155, trang "Chăm sóc CSI" liên tục gặp lỗi 504/524 (Gateway Timeout)
- Các Phase 155→159 đã cố gắng khắc phục (thêm filter, caching, tối ưu query, retry logic) nhưng lỗi vẫn tiếp diễn
- Không thể kiểm tra và khắc phục triệt để do không có truy cập trực tiếp vào server/database

## Giải pháp
Revert toàn bộ thay đổi CSI (Phase 155→159) về trạng thái Phase 154 — phiên bản hoạt động ổn định cuối cùng.

### Files reverted về Phase 154 (commit 5abfff8):
- `src/app/Services/CsiService.php` — Loại bỏ: dashboard-data endpoint gộp, query caching, MIN+JOIN optimization, statement timeout
- `src/app/Http/Controllers/CsiController.php` — Loại bỏ: apiDashboardData(), caching logic, combined endpoint
- `src/resources/views/csi/index.blade.php` — Loại bỏ: first lesson date filter, retry logic, dashboard-data combined loading

### Thay đổi KHÔNG bị revert (giữ lại):
- `src/app/Console/Commands/RefreshDashboardCache.php` — Phase 158: pre-check database connection (không liên quan CSI 504)

### Uncommitted change cũng được revert:
- `src/routes/web.php` — Loại bỏ route `/api/csi/dashboard-data` (đã thêm trong các phase bị revert)

## Các Phase bị revert
| Phase | Commit | Mô tả |
|-------|--------|-------|
| 155 | 7876c89 | Thêm first lesson date filter |
| 156 | 5bec522 | Thêm query caching để tránh 524 |
| 157 | 14cbd5d | Tối ưu queries và retry logic |
| 159 | 728a65a | Tối ưu SQL (MIN+JOIN), statement timeout |

## Trạng thái sau revert
- Trang CSI sử dụng các endpoint riêng biệt: `/api/csi/summary`, `/api/csi/students`, `/api/csi/health-distribution`, v.v.
- Không có `/api/csi/dashboard-data` gộp
- Không có first lesson date filter
- Các tính năng Phase 154 đều hoạt động: filter button, avg lessons/week fix, first-3 lessons fix