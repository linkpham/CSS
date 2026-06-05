# Ticket final wording round

Date: 2026-05-26
Production: `https://crm.icanwork.vn/ticket/`

## Objective
Thực hiện vòng cuối để đồng bộ wording của Ticket module với chuẩn mới trên CRM root: giảm lẫn Anh-Việt, bỏ copy kỹ thuật dư thừa, và làm rõ các title / button / comparison labels / analytics copy.

## Main changes
### Navigation / shell
- `Ticket Module` -> `Phân hệ Ticket`
- `Điều hướng module` -> `Điều hướng phân hệ`
- `1 domain, 2 module` -> `1 domain, 2 phân hệ`
- breadcrumb `CRM Dashboard` -> `CRM học viên`

### Tabs / visible titles
- `Tổng quan issue` -> `Tổng quan lỗi`
- `Issue kỹ thuật` -> `Lỗi kỹ thuật`
- `Issue giáo viên` -> `Lỗi giáo viên`
- `Drill-down chi tiết` -> `Danh sách ticket chi tiết`
- `Drill-down issue list` -> `Danh sách ticket chi tiết`
- `Bộ phận hotspot` -> `Bộ phận nổi bật`
- `Chất lượng & coverage dữ liệu` -> `Chất lượng & độ phủ dữ liệu`
- `So sánh 2025 SpeakWell vs 2026 combined` -> `So sánh 2025 SpeakWell vs 2026 tổng hợp`
- `So sánh pattern lỗi` -> `So sánh mẫu lỗi`

### Analytics / comparison copy
- `Chênh issue` -> `Chênh lỗi`
- `Tổng issue dữ liệu` -> `Tổng lỗi dữ liệu`
- `Tổng issue` -> `Tổng lỗi`
- `Issue MoM` -> `Lỗi MoM`
- `Tỷ trọng lỗi Tech` -> `Tỷ trọng lỗi kỹ thuật`
- `Teacher patterns` -> `Mẫu lỗi giáo viên`
- `Teacher high-impact cases` -> `Case giáo viên ảnh hưởng lớn`
- `impact` -> `ảnh hưởng` ở các copy user-facing liên quan

### Teacher-cost / specialist sections
- `Chi phí GV trong issue kỹ thuật` -> `Chi phí GV trong lỗi kỹ thuật`
- `Chi phí GV trong issue giáo viên` -> `Chi phí GV trong lỗi giáo viên`

### Quick actions / misc
- `Làm mới Ticket` -> `Làm mới dữ liệu`
- `Xem popup` style wording was already removed previously; Ticket keeps `Xem ticket`
- PDF filename changed from `issue-analytics-dashboard-*` to `ticket-dashboard-*`

## Production verification
### Present on live `/ticket/`
- `Phân hệ Ticket · CRM`
- `Tổng quan lỗi`
- `Lỗi kỹ thuật`
- `Lỗi giáo viên`
- `Mẫu lỗi giáo viên`
- `Case giáo viên ảnh hưởng lớn`
- `So sánh mẫu lỗi`
- `Lỗi 2026`
- `Tổng lỗi dữ liệu`
- `Tổng lỗi ghi nhận`
- `Chi phí GV trong lỗi kỹ thuật`
- `Chi phí GV trong lỗi giáo viên`
- `Chênh lỗi`
- `Xem ticket`

### Confirmed absent on live `/ticket/`
- `Ticket Module`
- `Issue kỹ thuật`
- `Issue giáo viên`
- `Drill-down issue list`
- `combined`
- `Teacher high-impact cases`
- `Tổng quan issue`
- `Tổng issue dữ liệu`
- `Nhóm lỗi / category`
- `Chi phí GV trong issue kỹ thuật`
- `Chi phí GV trong issue giáo viên`
- `Chênh issue`

## Result
Ticket module đã được đồng bộ wording thêm một vòng cuối theo cùng phong cách với CRM root: ngắn gọn hơn, business-friendly hơn và ít lẫn Anh-Việt hơn ở các vùng người dùng nhìn thấy nhiều nhất.
