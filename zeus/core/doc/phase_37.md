# Phase 37 - Fix Class Size (Size lớp) in Usage Report Export

## Issue
- File kết quả từ chức năng "Báo cáo sử dụng" được đặt ở trong trang Doanh Thu `/revenue` chưa đúng: `Size lớp` bị trả về là "N/A", cần tìm mối liên hệ với bảng `tbl_group_classes` để trả về kết quả đúng nhất cho `Size lớp`

## Root Cause
- Trường `Size lớp` (class_size) đang được parse từ tên môn học (subject_name) thay vì lấy từ dữ liệu thực sự của lớp nhóm
- Method `parseClassSize()` chỉ tìm kiếm keywords trong tên môn học như "1-1", "1:1", "GROUP"... nhưng không phải tất cả môn học đều có thông tin này trong tên

## Solution
1. Sửa query `getOrderData()` trong `UsageReportService.php`:
   - LEFT JOIN với `tbl_group_classes as gc` thông qua điều kiện:
     - `gc.grpcls_tlang_id = ol.ordles_tlang_id` (cùng môn học)
     - `gc.grpcls_teacher_id = ol.ordles_teacher_id` (cùng giáo viên)
   - Thêm select `MAX(gc.grpcls_total_seats) as group_class_size` để lấy số chỗ ngồi tối đa từ group class

2. Thêm method mới `getClassSizeFromGroupClass()`:
   - Ưu tiên lấy `grpcls_total_seats` từ database
   - Map số chỗ ngồi sang display format:
     - 1 chỗ → "1:1" (cá nhân)
     - 2 chỗ → "1:2" (đôi)
     - 3 chỗ → "1:3" (nhóm nhỏ)
     - 4-6 chỗ → "1:6" (nhóm vừa)
     - 7-10 chỗ → "1:8" (nhóm lớn)
     - 11+ chỗ → "Group" (lớp học)
   - Fallback về `parseClassSize()` nếu không có dữ liệu group class

3. Cập nhật `buildReportRow()`:
   - Sử dụng `getClassSizeFromGroupClass()` thay vì `parseClassSize()`

4. Cập nhật tooltip trong `ProcessUsageReportExport.php`:
   - Mô tả nguồn dữ liệu mới từ `tbl_group_classes.grpcls_total_seats`

## Files Changed
- `src/app/Services/UsageReportService.php`
  - Modified `getOrderData()`: Added LEFT JOIN with `tbl_group_classes`
  - Added `getClassSizeFromGroupClass()`: New method to get class size from database
  - Modified `buildReportRow()`: Use new method for class_size
  - Deprecated `parseClassSize()`: Kept as fallback
  
- `src/app/Jobs/ProcessUsageReportExport.php`
  - Updated tooltip comment for `class_size` field

## Database Tables Involved
- `tbl_order_lessons`: Source of `ordles_tlang_id` and `ordles_teacher_id`
- `tbl_group_classes`: Source of `grpcls_total_seats`, linked via `grpcls_tlang_id` and `grpcls_teacher_id`

## Class Size Mapping Logic
| grpcls_total_seats | Display Value | Description |
|-------------------|---------------|-------------|
| 1 | 1:1 | Individual lesson |
| 2 | 1:2 | Pair lesson |
| 3 | 1:3 | Small group |
| 4-6 | 1:6 | Medium group |
| 7-10 | 1:8 | Large group |
| 11+ | Group | Class/Workshop |

## Testing
- Verify that "Báo cáo sử dụng" export shows correct class size
- Check that group classes with defined total_seats return proper size
- Ensure fallback works when no group class data is available (parses from subject name)
