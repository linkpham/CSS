# Phase 130 ✅

## Yêu cầu
1. Fix lỗi Chart.js `Cannot read properties of null (reading 'save')` trên trang Chăm sóc CSI
2. Chuyển menu `Chăm sóc CSI` thành submenu của `ICAN`, ngang hàng với LCMS, Quản trị GV, ...

## Thay đổi
### 1. Fix Chart.js error (`csi/index.blade.php`)
- Thêm guard clause cho tất cả 4 hàm render chart (healthChart, scoreChart, cssChart, teacherWarningChart)
- Reset chart instance về `null` sau khi destroy để tránh stale reference
- Skip rendering khi data rỗng hoặc tất cả count = 0 (đặc biệt doughnut chart bị crash khi data rỗng)

### 2. Di chuyển menu CSI vào ICAN (`layouts/app.blade.php`)
- Thêm link `Chăm sóc CSI` vào cuối danh sách submenu ICAN (sau LCMS)
- Xóa mục standalone CSI section bên ngoài accordion
- Sử dụng `sidebar-link` style giống các mục LCMS, Quản trị GV
