# Phase 74 - Fix Weekly Plan Export & UI Structure

## Yêu cầu
1. Fix lỗi Alpine.js khi nhấn nút "Xuất Plan"
2. Loại bỏ nội dung `🌍 Tổng số ca theo Quốc gia GV (Tuần)` trong sheet Unscheduled
3. Cập nhật UI block với cấu trúc class size breakdown

## Giải pháp

### 1. Fix Alpine.js Error
- **Nguyên nhân**: `x-for` template sử dụng key `week.year_week` nhưng API trả về `week_of_year`
- **Fix**: Đổi key thành index-based: `(week, weekIdx) in weeks` với key `'header-' + weekIdx`

### 2. Remove Weekly Data from Unscheduled Sheet
- **File**: `src/app/Services/WeeklyPlanService.php`
- **Thay đổi**: Refactor `buildTeacherCountrySheet()` để chỉ hiển thị dữ liệu Unscheduled, loại bỏ phần weekly

### 3. UI Block với Class Size Breakdown
- **File**: `src/resources/views/dashboard/daily-ops.blade.php`
- **API mới**: `getTeacherCountryWeeklyWithClassSize()` - trả về dữ liệu theo tuần → class_size → nationality
- **UI mới**: Hiển thị table với cấu trúc Plan.xlsx:
  - Header: Tuần, Từ ngày, Đến ngày
  - SPW row (orange header)
  - Tổng ca dự kiến đang chạy
  - 1v1/1v2/1v3/1v8 với breakdown theo nationality (Vietnamese, Philippines, Native 1, Native 2)

## Files Changed
- `src/app/Services/WeeklyPlanService.php` - Add `getTeacherCountryWeeklyWithClassSize()`, refactor `buildTeacherCountrySheet()`
- `src/app/Http/Controllers/DashboardController.php` - Update `apiTeacherCountryWeekly()` to use new method
- `src/resources/views/dashboard/daily-ops.blade.php` - Fix Alpine.js keys, update table structure with class sizes

## Testing
```bash
docker exec zeus-dashboard-app php artisan tinker --execute="
\$data = \App\Services\WeeklyPlanService::getTeacherCountryWeeklyWithClassSize();
print_r(array_keys(\$data['weeks'][0]['by_class_size']));
"
```

