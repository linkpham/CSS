# Phase 139 - Client-side Program Tab Switching (No Page Navigation)

## Yêu cầu
- Chỉnh sửa lại trang `KPI` theo yêu cầu sau: Khi chuyển giữa các tab `All`, `SPEAKWELL` và `EASY SPEAK` thì không chuyển trang như `/?program=all` hay `/program=speakwell` hay `program=easyspeak`.

- Chỉnh sửa lại trang `Vận hành` theo yêu cầu sau: Khi chuyển giữa các tab `All`, `SPEAKWELL` và `EASY SPEAK` thì không chuyển trang như `/?daily-ops=all` hay `/daily-ops=speakwell` hay `daily-ops=easyspeak`.

## Giải pháp: PJAX-style Content Replacement

### Thay đổi chính

1. **`kpi-program-tabs.blade.php`**: Chuyển từ `<a href>` sang `<button>` với Alpine.js
   - Tab active state quản lý bằng Alpine.js `x-data` thay vì server-side Blade
   - Click tab gọi `switchProgramContent(program)` thay vì navigate

2. **`index.blade.php` (KPI)**: 
   - Thêm `<div id="program-content">` wrapper cho nội dung dưới tabs
   - Chuyển `x-data="dashboardFilter()"` vào `#program-content` div
   - Đổi `const activeProgram` → `window.activeProgram` trong scripts
   - Thêm `data-page-script` attribute cho script tag

3. **`daily-ops.blade.php` (Vận hành)**:
   - Thêm `<div id="program-content">` wrapper cho nội dung dưới tabs
   - Đổi `const activeProgram` → `window.activeProgram` trong scripts
   - Thêm `data-page-script` attribute cho script tag

### Cách hoạt động
- Khi click tab, hàm `switchProgramContent()` được gọi
- Fetch trang với `?program=xxx` qua AJAX (không thay đổi URL)
- Parse HTML response, trích xuất `#program-content`
- Destroy Chart.js instances và Alpine.js tree cũ
- Replace innerHTML với nội dung mới
- Re-execute page script (cập nhật chart data, function definitions)
- Initialize Alpine.js tree mới trên content mới
- Fallback: navigate bình thường nếu PJAX thất bại

## Files Changed
- `src/resources/views/components/kpi-program-tabs.blade.php`
- `src/resources/views/dashboard/index.blade.php`
- `src/resources/views/dashboard/daily-ops.blade.php`
