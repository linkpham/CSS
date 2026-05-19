# Phase 114
Tiếp tục chỉnh sửa block `📅 Danh sách GV khả dụng theo khung giờ` theo yêu cầu sau:

- Checkbox `Chỉ GV Trial` phải là `Lọc bỏ GV dạy Trial`, khi tick thì sẽ không tính số giáo viên dạy Trial. 
- Khun giờ hiện tại là khung giờ chẵn. Cần phải có thêm tab hiển thị theo khung giờ lẻ từ `ordles_teacher_starttime`, `ordles_teacher_endtime` như trong bảng `tbl_order_lessons` .  Tab khung giờ lẻ là mặc định.

- Bổ sung thêm khả năng tìm lịch để lọc toàn bộ các giáo viên khả dụng. Ví dụ hiện toàn bộ giáo viên khả dụng khi tìm lịch 19:10 thứ 3 và thứ 5 với lịch bắt đầu từ 1/4/2026. 
- Bổ sung cho phép lọc loại giáo viên: 
	- `VN` (Vietnam)
	- `PHIL` (Philippines)
	- `US`, `GB`, `UK`, `CA`, `AU`, `NZ`, `IE`, `ZA` → `NN` (Native English speakers)