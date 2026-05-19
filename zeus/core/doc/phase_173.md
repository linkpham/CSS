# Phase 173
- Trong `👨‍🎓 Danh sách Học viên`, buổi đầu tiên cần lấy là buổi sau trial (sau khi đóng tiền) chứ không phải là buổi Trial.

## Thay đổi

### CsiService.php
- Thêm method `excludeTrialSubject()` để lấy CONF_TRIAL_SUBJECT_ID từ tbl_configurations
- Thêm `l.ordles_tlang_id` vào `joined` CTE để có thể lọc theo subject ID
- Loại bỏ buổi Trial khỏi `first_3_ranked` CTE (cả 2 trường hợp: có/không có date_from filter)
  - Trường hợp không có date_from: thêm WHERE clause lọc trial trong `first_3_ranked`
  - Trường hợp có date_from: thêm điều kiện lọc trial trong `first3_source`

### csi/index.blade.php
- Cập nhật tooltip "buổi học đầu tiên" → ghi rõ là buổi sau Trial
- Cập nhật tooltip "%TC 3 buổi đầu" → ghi rõ buổi Trial không được tính
- Cập nhật legend "Buổi 1/2/3" → "3 buổi đầu tiên sau Trial"
- Cập nhật tiêu đề detail modal "3 buổi học đầu tiên" → "3 buổi học đầu tiên (sau Trial)"

## Status: ✅ Hoàn thành