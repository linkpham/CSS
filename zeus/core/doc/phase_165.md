# Phase 165 ✅

## Vấn đề
Sau khi Phase 163 thêm `INNER JOIN tbl_orders` để loại bỏ học viên TRIAL, tất cả các API endpoint của trang CSI trả về lỗi 524 (timeout):
- `/api/csi/summary`
- `/api/csi/health-distribution`
- `/api/csi/score-distribution`
- `/api/csi/css-performance`
- `/api/csi/teacher-warning`

## Nguyên nhân
Các câu lệnh SQL quá chậm do:
1. **CTE cross-references với `IN (SELECT … FROM CTE)`** – MySQL materialized CTE không có index, khiến lookup rất chậm
2. **Scan bảng trùng lặp** – `first3_all_lessons` query lại cùng bảng `tbl_order_lessons` + `tbl_orders` với điều kiện gần giống `lessons_base`
3. **3 CTE thừa** – `lessons_base → extras_one → joined` có thể gộp thành 1

## Giải pháp
Tối ưu `CsiService.php`:
1. **Gộp `baseCte()`**: 3 CTE (`lessons_base`, `extras_one`, `joined`) → 1 CTE (`joined`) sử dụng correlated scalar subquery cho acceptance_code (sử dụng index `ole_ordles_id`)
2. **Tối ưu `fullCte()`**:
   - Không có `date_from` filter: tái sử dụng `joined` cho first-3 ranking (không cần query thêm)
   - Có `date_from` filter: dùng `INNER JOIN` thay vì `IN (SELECT … FROM CTE)`
   - Loại bỏ hoàn toàn `first3_all_lessons` và `first3_extras` CTE

## Kết quả
Tất cả endpoint hoạt động bình thường, thời gian phản hồi ~2-5 giây (trước đó timeout >100 giây).