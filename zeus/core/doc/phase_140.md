# Phase 140 - Comprehensive Caching for Program Tab Switching

## Yêu cầu
- Mỗi lần switch qua lại `All`, `SPEAKWELL` và `EASY SPEAK`  đều phải chờ rất lâu. Hãy giải quyết vấn đề caching 1 cách triệt để.

## Nguyên nhân
1. **Server-side**: `RefreshDashboardCache` command chỉ pre-cache cho program `all` (mặc định), không cache cho `speakwell` và `easyspeak`. Khi switch tab, server phải tính toán lại toàn bộ dữ liệu từ đầu.
2. **Client-side**: Mỗi lần switch tab đều fetch lại HTML từ server (PJAX), dù dữ liệu đã được load trước đó.

## Giải pháp

### 1. Server-side: Pre-cache ALL 3 programs (`RefreshDashboardCache.php`)
- Refactor `handle()` method: tách cache operations ra method `getCacheOperations()`
- Loop qua cả 3 programs (`all`, `speakwell`, `easyspeak`)
- Gọi `$dashboardService->setProgram($program)` trước mỗi iteration
- Mỗi `getCached()` tự động append `.prog_$program` vào cache key
- Tổng số items cache = items × 3 programs
- Progress bar hiển thị program đang cache

### 2. Client-side: HTML caching + Background pre-fetch (`kpi-program-tabs.blade.php`)
- **`window._programContentCache`**: Object lưu HTML + script của mỗi program tab
- **Cache on load**: Khi page load, cache ngay HTML của program hiện tại
- **Background pre-fetch**: Sau 500ms, fetch HTML của 2 programs còn lại trong background
- **Instant switch**: Khi click tab, check cache trước → nếu có → apply ngay (không fetch)
- **Fallback**: Nếu cache miss → fetch + cache + apply như cũ
- **`_applyProgramContent()`**: Method chung cho cả cache-hit và fetch paths

### 3. Refresh button ("Làm mới") cũng được cập nhật (`app.blade.php`)
- Clear `window._programContentCache` khi nhấn Làm mới
- Pre-fetch tất cả 3 programs đồng thời warm cả server + client cache

## Files Changed
- `src/app/Console/Commands/RefreshDashboardCache.php` — Loop 3 programs
- `src/resources/views/components/kpi-program-tabs.blade.php` — Client-side cache + pre-fetch
- `src/resources/views/layouts/app.blade.php` — Clear client cache on refresh