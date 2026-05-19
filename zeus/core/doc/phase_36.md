# Phase 36 - Fix Teacher Nationality in Usage Report Export

## Issue
- File kết quả từ chức năng "Báo cáo sử dụng" được đặt ở trong trang Doanh Thu `/revenue` chưa đúng: Dữ liệu của trường `Quốc Tịch GV` phải được liên kết với dữ liệu bảng `tbl_countries`

## Root Cause
- Trường `Quốc Tịch GV` (teacher_nationality) đang được parse từ tên môn học (subject_name) thay vì lấy từ dữ liệu quốc gia thực sự của giáo viên
- Method `parseTeacherNationality()` chỉ đoán quốc tịch dựa trên keywords trong tên môn học như "VN", "VIETNAM", "PHIL", "NATIVE"...

## Solution
1. Sửa query `getOrderData()` trong `UsageReportService.php`:
   - JOIN với `tbl_users as teacher` thông qua `ordles_teacher_id` để lấy thông tin giáo viên
   - JOIN với `tbl_countries as tc` thông qua `teacher.user_country_id` để lấy quốc gia của giáo viên
   - Thêm select `teacher_country_code` và `teacher_country_name` từ `tbl_countries`

2. Thêm method mới `getTeacherNationalityFromCountry()`:
   - Ưu tiên lấy country_code từ database (từ `tbl_countries`)
   - Map các country code phổ biến sang display name phù hợp:
     - `VN` → `VN` (Vietnam)
     - `PH` → `PHIL` (Philippines)
     - `US`, `GB`, `UK`, `CA`, `AU`, `NZ`, `IE`, `ZA` → `NN` (Native English speakers)
   - Fallback về `parseTeacherNationality()` nếu không có dữ liệu country

3. Cập nhật `buildReportRow()`:
   - Sử dụng `getTeacherNationalityFromCountry()` thay vì `parseTeacherNationality()`

## Files Changed
- `src/app/Services/UsageReportService.php`
  - Modified `getOrderData()`: Added JOINs with `tbl_users` (teacher) and `tbl_countries`
  - Added `getTeacherNationalityFromCountry()`: New method to get nationality from database
  - Modified `buildReportRow()`: Use new method for teacher_nationality
  - Deprecated `parseTeacherNationality()`: Kept as fallback

## Database Tables Involved
- `tbl_order_lessons`: Source of `ordles_teacher_id`
- `tbl_users` (as teacher): Source of `user_country_id`
- `tbl_countries`: Source of `country_code` and `country_identifier`

## Testing
- Verify that "Báo cáo sử dụng" export shows correct teacher nationality
- Check that country codes are properly mapped (VN, PHIL, NN, etc.)
- Ensure fallback works when teacher country data is missing

