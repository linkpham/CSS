# Phase 18 ✅

## Yêu cầu
- Tiến trình chờ caching `Starting dashboard cache refresh...`  trong DEPLOY_LOCAL.sh và DEPLOY_SERVER.sh có TUI khá kém, tạo cảm giác chờ lâu. Hãy cải thiện TUI để biết tiến trình caching.

## Giải pháp

### 1. Cải thiện RefreshDashboardCache Command
**File**: `src/app/Console/Commands/RefreshDashboardCache.php`

**Thay đổi**:
- Thêm `CACHE_LABELS` constant để hiển thị tên dễ đọc cho từng cache item (63 items)
- Thay thế Laravel progress bar mặc định bằng custom progress bar:
  - Visual progress bar: `[████████░░░░░░░░░░░░░░░░░░░░░░]`
  - Phần trăm hoàn thành: `35%`
  - Counter: `(22/63)`
  - Tên item đang xử lý: `⏳ Logins by Hour...`
  - Thời gian xử lý từng item (trong TTY mode): `(1.2s)`
- Hỗ trợ cả TTY mode (terminal) và non-TTY mode (docker exec):
  - TTY: Overwrite line với `\r` để hiển thị realtime
  - Non-TTY: Print từng dòng để thấy tiến trình

**Output mới**:
```
📊 Dashboard Cache Refresh
   Total items to cache: 63

   [███████████░░░░░░░░░░░░░░░░░░░]  35% (22/63) ⏳ Logins by Hour      ...
   [███████████░░░░░░░░░░░░░░░░░░░]  37% (23/63) ⏳ Logins by Day       ...
   [████████████░░░░░░░░░░░░░░░░░░]  38% (24/63) ⏳ Logins by Source    ...

✓ Successfully cached 63/63 items in 45.23s
  Cache refreshed at: 2026-01-14 15:30:00
```

### 2. Cập nhật Deploy Scripts
**Files**: `DEPLOY-LOCAL.sh`, `DEPLOY-SERVER.sh`

**Thay đổi**: Thêm `-t` flag cho docker exec để enable TTY mode, cho phép progress bar hiển thị đẹp hơn:
```bash
# Trước
docker exec zeus-dashboard-app php artisan dashboard:refresh-cache

# Sau  
docker exec -t zeus-dashboard-app php artisan dashboard:refresh-cache
```

## Files đã sửa
- `src/app/Console/Commands/RefreshDashboardCache.php`
  - Thêm `CACHE_LABELS` constant với 63 human-readable labels
  - Cập nhật `handle()` method với custom progress bar
  - Hỗ trợ TTY và non-TTY output modes
  
- `DEPLOY-LOCAL.sh`
  - Thêm `-t` flag cho docker exec khi chạy cache refresh

- `DEPLOY-SERVER.sh`
  - Thêm `-t` flag cho docker exec ở cả upgrade mode và full installation mode

- `doc/phase_18.md`
