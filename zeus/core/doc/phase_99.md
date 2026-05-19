# Phase 99

## Yêu cầu
Bảng `📋 Chi tiết hủy ca học` cần là 1 bảng đầy đủ toàn bộ số ca hủy (ví dụ Tổng hủy là 2366 thì trong danh sách phải có đủ 2366 bản ghi). Bảng danh sách này cần phải có cơ chế phân trang. Bảng danh sách phải có các bộ lọc danh sách ca hủy theo từ Giáo Viên, từ Học Viên và từ Admin. Ưu tiên hiển thị danh sách ca hủy của Giáo Viên trước, rồi đến Học viên và cuối cùng là Admin. Phải cho phép tìm kiếm tên giáo viên, học viên, trong danh sách.

## Thực hiện

### Backend (DashboardService + DashboardController)
- Thêm tham số `page`, `per_page`, `user_type_filter`, `search` vào API `/api/cancellation-stats`
- Server-side pagination với `OFFSET` / `LIMIT`
- Lọc theo `sesslog_user_type` (1=Học viên, 2=Giáo viên, 3=Admin)
- Tìm kiếm theo tên giáo viên/học viên (LIKE trên `user_first_name` + `user_last_name`)
- Sắp xếp ưu tiên: `FIELD(sl.sesslog_user_type, 2, 1, 3)` — GV → HV → Admin
- Trả về `pagination` object: `current_page`, `per_page`, `total`, `last_page`
- Summary cards luôn hiển thị tổng chưa lọc

### Frontend (daily-ops.blade.php - Alpine.js)
- Thêm properties: `pagination`, `detailsLoading`, `detailFilter`, `searchQuery`
- Filter tabs: Tất cả / Giáo viên / Học viên / Admin (mỗi tab hiện count)
- Search input với debounce 400ms, hỗ trợ Escape để xóa
- Pagination controls: First / Prev / Page numbers / Next / Last
- `_fetchDetails()`: gọi API riêng chỉ reload details (không reload summary)
- Export Excel: fetch tất cả bản ghi (không phân trang) trước khi xuất CSV
- Loading spinner riêng cho phần details khi filter/search/paginate

## Status: ✅ Hoàn thành