# Phase 219
- Tốt. Trong `👨‍🎓 Danh sách Học viên` của trang `Chăm sóc CSI`, cần có cột ghi rõ tên các chương trình mà học viên đó đang theo học ví dụ: SPEAKWELL (lấy theo ordles_tlang_id: 533, 558, ...),  EASYSPEAK (lấy theo ordles_tlang_id: 403, 404, ...)

## Thực hiện
- Thay đổi cột "Khóa học" thành "Chương trình" hiển thị tên chương trình (SPEAKWELL / EASYSPEAK) thay vì mã khóa học
- Phân loại dựa trên ordles_tlang_id: EASYSPEAK (403, 404, 471, 582, 583, 584, 585, 586), còn lại là SPEAKWELL
- Cập nhật tooltip, legend, và Excel export tương ứng
- Bỏ JOIN tbl_teach_languages (không cần thiết nữa) → tối ưu query