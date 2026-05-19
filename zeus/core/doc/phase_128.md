# Phase 128

- Tìm kiếm theo student ID vẫn không theo trường `stu_user_id` mà bị theo trường `stu_id`. Ví dụ khi gõ `358` trong hộp tìm kiếm `👨‍🎓 Chi tiết Tiến độ Học viên` mà không tìm ra được  trong khi tìm trong CSDL thì `stu_user_id = '358'` thì có tồn tại học sinh.