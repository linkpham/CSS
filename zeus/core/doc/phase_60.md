# Phase 60 - Weekly Plan Export

## Yêu cầu
Bổ sung vào trang Vận hành (/daily-ops?program=speakwell) chức năng xuất ra file excel theo định dạng giống như sheet Plan trong `/Users/que/Downloads/Plan.xlsx`. Các tuần được tự xuất theo lựa chọn range tháng trong năm của người dùng.

## Phân tích
File Plan.xlsx chứa:
- **Sheet Plan**: Số ca dự kiến theo tuần, phân loại theo:
  - Class size: 1v1, 1v2, 1v3, 1v8
  - Teacher nationality: Vietnamese, Philippines, Native 1, Native 2
- **Source SPW**: Dữ liệu nguồn từ hệ thống quản lý học viên SPW

## Thay đổi

### 1. Tạo Service: `app/Services/WeeklyPlanService.php`
- Query tbl_order_lessons với filter SpeakWell subjects
- Join với tbl_group_classes để lấy class_size (grpcls_total_seats)
- Join với tbl_countries để lấy teacher nationality
- Tính toán số ca theo tuần trong range tháng được chọn
- Generate Excel file với format giống Plan.xlsx

### 2. Thêm API endpoints trong `DashboardController.php`
- `GET /api/export-weekly-plan?from=YYYY-MM&to=YYYY-MM` - Xuất file Excel
- `GET /api/download-plan/{filename}` - Download file đã xuất

### 3. Thêm routes trong `routes/web.php`
```php
Route::get('/export-weekly-plan', [DashboardController::class, 'apiExportWeeklyPlan']);
Route::get('/download-plan/{filename}', [DashboardController::class, 'apiDownloadPlan']);
```

### 4. Cập nhật UI `resources/views/dashboard/daily-ops.blade.php`
- Thêm nút "📥 Xuất Plan" ở header
- Modal chọn range tháng (from/to)
- Alpine.js component `weeklyPlanExport()` để handle export

## Cấu trúc file Excel

| Tuần | | | Tuần 1 | Tuần 2 | ... | SUM |
|------|---|---|--------|--------|-----|-----|
| Từ ngày | | | 01/02/2026 | 08/02/2026 | ... | |
| Đến ngày | | | 07/02/2026 | 14/02/2026 | ... | |
| SPW | | | | | | |
| | Tổng ca dự kiến | | 262 | 719 | ... | 2385 |
| | 1v1 | Sum | ... | ... | | |
| | | Vietnamese | ... | ... | | |
| | | Philippines | ... | ... | | |
| | | Native 1 | ... | ... | | |
| | | Native 2 | ... | ... | | |
| | 1v2 | Sum | ... | ... | | |
| | ... | | | | | |

## Status: ✅ COMPLETED