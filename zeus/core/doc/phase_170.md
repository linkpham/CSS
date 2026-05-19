# Phase 170 ✅
- Các span `class="tooltip-content tooltip-wide"` của `info-tooltip` khi nằm trong thẻ `<th>` đều bị che mất nội dung tooltip, không đọc được. Hãy tìm cách chỉnh sửa.

## Nguyên nhân
- `.table-container` có `overflow-x: auto` tạo ra overflow clipping context
- Tooltip dùng `position: absolute` bị cắt bởi overflow context của container cha

## Giải pháp
- **CSS**: Tooltip trong `<th>` chuyển sang `position: fixed` để thoát khỏi overflow clipping
- **JS**: Thêm event delegation script tính toán vị trí viewport chính xác khi hover:
  - Mặc định hiển thị phía trên icon
  - Tự động chuyển xuống dưới nếu không đủ chỗ phía trên
  - Giữ tooltip trong viewport theo chiều ngang
  - Re-position khi table scroll ngang

## File thay đổi
- `src/resources/views/layouts/app.blade.php` — CSS override cho `th .info-tooltip .tooltip-content` + JS positioning script