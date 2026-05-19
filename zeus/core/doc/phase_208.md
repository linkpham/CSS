# Phase 208
- Trong trang `Quản trị GV` (/teacher-management), tại block `🔄 Học viên bị thay đổi Giáo viên`, cần loại các buổi học trial ra (không có môn 533), ví dụ: `WHERE `ordles_tlang_id` IN (389,390,392,405,406,407,411,412,413,414,415,416,558,560,562,564,567,568,569,571,572,574,575,576,577,580,581)`

- Trong trang `Quản trị GV` (/teacher-management), tại block `🔄 Học viên bị thay đổi Giáo viên`, cần bổ sung thêm chức năng lọc theo quốc tịch giáo viên. 

- Mục `📅 Đến ngày` tại block `🔄 Học viên bị thay đổi Giáo viên` mặc định là ngày hiện tại.  

- Cần làm rõ lại là đổi giáo viên có nghĩa là do giáo viên nghỉ, hoặc do phụ huynh yêu cầu đổi 

- `Không tìm thấy buổi học SPW (đã hoàn thành) nào cho HV này trong khoảng thời gian đã chọn.` thì `đã hoàn thành` có phải là buổi học kết thúc (không thành công?) đúng không? 

- Tự kết nối csdl qua `zeus-aurora-cluster-prod.cluster-csrn8dqqphhg.ap-southeast-1.rds.amazonaws.com` để kiểm tra các trường hợp hiển thị `Không tìm thấy buổi học SPW (đã hoàn thành) nào cho HV này trong khoảng thời gian đã chọn.`. Tôi cần mở `👁️ Xem`thì tất cả các buổi học của học viên phải được hiện ra, kèm trạng thái và sự thay đổi giáo viên.  Hãy kiểm tra với id học viên 5887, tên Phú Nguyễn, email Ngxuanthao174@gmail.com để  đảm bảo là có học với 4 giáo viên và chỉ có 3 lần đổi. Kết n