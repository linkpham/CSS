# CRM wording + note cleanup

Date: 2026-05-26
Production: `https://crm.icanwork.vn`

## Scope
- Tiếp tục dọn wording action sau đợt cleanup `Mở hồ sơ`
- Bỏ các chú thích/caption dư thừa, lặp lại hoặc mang tính hướng dẫn nội bộ

## Wording updated
- `Xem popup` -> `Xem nhanh`
- `Mở danh sách` -> `Xem danh sách`
- `Tra cứu` -> `Xem lịch sử`
- `Mở gói học` -> `Xem gói học`
- Cột `Tra cứu` trong bảng package focus -> `Thao tác`

## Notes removed / simplified
Đã bỏ các note không cần thiết ở:
- Hồ sơ học viên tổng quan
- Bộ lọc scope chung của learner profile
- Header danh sách trong Journey / Live / LCMS
- Header Live / LCMS / Packages có caption lặp lại
- Worklist học viên
- Reports `Ưu tiên can thiệp`
- Quick-check note `Đang kiểm tra logic hiển thị...`
- Một số caption hướng dẫn như:
  - `Góc nhìn 360°...`
  - `Nơi nhận toàn bộ cohort...`
  - `Mỗi dòng ưu tiên 2 action...`
  - note mô tả lặp lại ở package history / expiring packages

## Verification
### Local/static markers
Confirmed present:
- `Xem nhanh`
- `Xem danh sách`
- `Xem lịch sử`
- `Đi tới hồ sơ học viên`

Confirmed absent:
- `Xem popup`
- `Mở danh sách`
- `Tra cứu`
- `Góc nhìn 360° theo từng học viên`
- `Nơi nhận toàn bộ cohort từ Command Center`
- `Mỗi dòng ưu tiên 2 action: mở hồ sơ hoặc xem popup nhanh.`
- `Đang kiểm tra logic hiển thị...`

### Production HTML
Live homepage verified with the same markers above.

## Result
CRM root hiện dùng wording ngắn gọn, đồng nhất hơn và bớt chú thích rườm rà trong các learner/worklist flows.
