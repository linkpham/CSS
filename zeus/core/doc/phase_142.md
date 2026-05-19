# Phase 142 - Remove SPEAKWELL & EASY SPEAK tabs (caching performance)
- Loại bỏ tab và code liên quan đến `SPEAKWELL` và `EASY SPEAK` vì tốc độ caching quá chậm. Chỉ giữ lại All cho cả trang `KPI` và trang `Vận hành`.

## Changes
- **kpi-program-tabs.blade.php**: Replaced 3-tab UI with simple "All" indicator (matching program-tabs style)
- **index.blade.php**: Removed embedded JSON `_programContentEmbedded` script for tab switching
- **daily-ops.blade.php**: Removed embedded JSON `_programContentEmbedded` script for tab switching
- **DashboardController**: Simplified `index()` and `dailyOps()` to only load 'all' program (no loop over 3 programs)
- **DashboardService**: Changed `VALID_PROGRAMS` from `['all', 'speakwell', 'easyspeak']` to `['all']`
- **RefreshDashboardCache**: Only caches 'all' program (1/3 of previous cache work = ~3x faster)
- **app.blade.php**: Simplified cache pre-warming to only warm 'all' program