# Phase 213
- Trong `🔄 Chi tiết thay đổi GV` loại bỏ các buổi `Đã Hủy`. 
- Cho phép các roles quản trị giáo viên với role_id (9,11) được phép truy cập trang Quản trị GV. 
- Trong trang `Quản trị GV` (/teacher-management), tại block `🔄 Học viên bị thay đổi Giáo viên`, cần có cột ghi rõ tên các khóa học mà học viên đó đang theo học. Ví dụ, học viên `ID: 2782, Họ tên: Bảo Phạm,  EMAIL: thanhcong.technic@gmail.com` có học 2 khóa ` Kid’s Box - Movers` và `Kid’s Box - Starters`. 
- Trong trang `Quản trị GV` (/teacher-management), tại block `🔄 Học viên bị thay đổi Giáo viên`, cần điều chỉnh lại vấn đề sau. Thực chất có học viên học nhiều khóa học một lúc, ví dụ như học viên  `ID: 2782, Họ tên: Bảo Phạm,  EMAIL: thanhcong.technic@gmail.com` học 2 khóa ` Kid’s Box - Movers` và `Kid’s Box - Starters` với thời gian như sau:
```
	•	Thứ hai: 20:55-21:25
	•	Thứ tư: 20:55-21:25
	•	Thứ sáu: 20:55-21:25
	•	GV: Huỳnh Nhi
	•	KHÓA HỌC: Kid’s Box - Movers
```
và 
```
	•	Thứ sáu: 18:35-19:05
	•	Chủ nhật: 20:20-20:50
	•	GV: Kim Thu 2
	•	KHÓA HỌC: Kid’s Box - Starters
```

Như vậy không được tính là có đổi giáo viên theo kiểu:
```
T6 11:35

Kim Thu 2
← GV trước: Huỳnh Nhi
```
vì đúng là T6 11:35 do giáo viên Kim Thu 2 dạy và thuộc khóa `Kid’s Box - Starters` chứ không hề có sự đổi giáo viên.

Hãy phân tách rõ ra là có sự thay đổi giáo viên ở khóa học nào chứ không tính lẫn cả 2 như vậy. 
Tự kết nối csdl qua `zeus-aurora-cluster-prod.cluster-csrn8dqqphhg.ap-southeast-1.rds.amazonaws.com` để kiểm tra.