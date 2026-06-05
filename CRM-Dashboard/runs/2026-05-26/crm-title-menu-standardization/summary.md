# CRM title + menu standardization

Date: 2026-05-26
Production: `https://crm.icanwork.vn`

## Objective
Chuẩn hóa tiếp các title, menu description và wording còn lẫn Anh-Việt hoặc thiên kỹ thuật trong CRM root.

## Main changes
### Menu / navigation
Updated `TAB_CONFIG` and related labels:
- `Live` -> `Buổi học live`
- `Command Center` -> `Trung tâm điều hành`
- menu descriptions rewritten sang tiếng Việt business-friendly hơn
- learner profile tab labels đồng bộ theo wording mới

### KPI / section titles
Updated visible titles such as:
- `Population hợp nhất` -> `Tổng học viên hợp nhất`
- `Renewal Rate` -> `Tỷ lệ gia hạn`
- `Cash Revenue` -> `Doanh thu thu tiền`
- `Forecast Renewal Cash` -> `Dự báo doanh thu gia hạn`
- `Risk & Renewal` -> `Rủi ro & gia hạn`
- `Renewal Status` -> `Trạng thái gia hạn`
- `Renewal Rate theo sức khỏe` -> `Tỷ lệ gia hạn theo sức khỏe`
- `Worklist học viên` -> `Danh sách xử lý học viên`
- `Command Center · 4 dashboard ưu tiên` -> `Trung tâm điều hành · 4 bảng ưu tiên`
- `Renewal matrix` -> `Ma trận gia hạn`

### Learner profile / labels
Standardized:
- `Learner scope dùng chung` -> `Phạm vi học viên dùng chung`
- `Learner scope của worklist` -> `Phạm vi học viên của danh sách xử lý`
- `Class size` -> `Quy mô lớp`
- status sublabels:
  - `Status 1 + 2` -> `Đã xếp lịch hoặc chưa xếp lịch`
  - `Status 3` -> `Đã học xong`
  - `Status 2` -> `Đã xếp lịch`
  - `Status 1` -> `Chưa xếp lịch`
  - `Status 4` -> `Đã hủy`
- `Xem popup` -> `Xem nhanh`
- `Mở dashboard` -> `Xem bảng`

### LCMS wording
Refined LCMS-related text:
- ưu tiên `LCMS` thay vì `LCMS thật` ở một số title
- đổi `fallback proxy` sang `chỉ báo thay thế`
- section note đổi sang tiếng Việt: `Tiến độ và điểm theo từng section...`

### Export headers
Standardized export headers to Vietnamese:
- `Student ID` -> `Mã học viên`
- `Target Score` -> `Điểm target`
- `Base Score` -> `Điểm base`
- `Renewal Status` -> `Trạng thái gia hạn`
- etc.

## Verification
### Local/static markers
Confirmed present:
- `Trung tâm điều hành`
- `Tổng học viên hợp nhất`
- `Buổi học live`
- `Tỷ lệ gia hạn theo sức khỏe`
- `Doanh thu thu tiền`
- `Dự báo doanh thu gia hạn`
- `Phạm vi học viên dùng chung`
- `Quy mô lớp`
- `Xem bảng`

Confirmed absent:
- `Command Center`
- `Worklist học viên`
- `Renewal Status`
- `Renewal Rate`
- `Learner scope dùng chung`
- `Class size`
- `Xem popup`
- `Mở dashboard`

### Production HTML
Verified same markers live on homepage HTML after deploy.

## Result
CRM root hiện đồng nhất hơn về title, menu description và action wording; giảm rõ tình trạng lẫn Anh-Việt ở các khu vực người dùng nhìn thấy nhiều nhất.
