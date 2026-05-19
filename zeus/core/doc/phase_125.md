# Phase 125

## Lỗi hiển thị các charts trên trang LCMS

### Vấn đề
```
chart.js:13 
 Uncaught TypeError: Cannot read properties of null (reading 'save')
```
Lỗi xảy ra khi Chart.js cố vẽ trên canvas đã bị Alpine.js xóa khỏi DOM.

### Nguyên nhân
- Các canvas chart nằm trong `<template x-if>` — Alpine.js sẽ xóa/tạo lại DOM elements khi điều kiện thay đổi
- Chart.js animation (requestAnimationFrame) vẫn chạy khi canvas đã bị xóa khỏi DOM
- Canvas context trở thành null → lỗi `save()` trong rendering pipeline

### Giải pháp
1. **Thay `<template x-if>` bằng `<div x-show>` cho 4 chart containers** (courseCompletionChart, sectionDistChart, scoreDistChart, completionTrendChart) — giữ canvas luôn trong DOM
2. **Thêm null guard cho canvas context** trong tất cả render functions (`if (!ctx) return;`)
3. **Dùng `requestAnimationFrame`** sau `$nextTick` để đảm bảo layout đã computed trước khi tạo chart
4. **Destroy charts trong `init()`** trước khi re-render để tránh stale references khi retry

### Files thay đổi
- `src/resources/views/dashboard/lcms.blade.php`
