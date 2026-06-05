# Ticket cause-detail deepen

Date: 2026-05-26
Production: `https://crm.icanwork.vn/ticket/`

## User request
Rà phần deep dive data của Ticket để xem có thể tìm thêm và chia chi tiết hơn nguyên nhân lỗi hay không; nếu được thì bổ sung.

## What was added
### 1. Bóc tách nguyên nhân sâu hơn
Updated `/mnt/f/code/caresoft/CRM-Dashboard/CRM-Dashboard/src/services/analyticsService.js`:
- added `deriveCauseDetail(ticket, causeGroup)`
- added `deriveCauseEvidence(ticket, causeDetail)`
- each ticket now has:
  - `causeDetail`
  - `causeEvidence`
- dashboard now returns:
  - `rootCauseDetails`
- each focus area now returns:
  - `causeDetails`

### 2. Deep-dive UI expanded
Updated `/mnt/f/code/caresoft/CRM-Dashboard/CRM-Dashboard/public/index.html`:
- tab **Nguyên nhân & xử lý** now has table **Nguyên nhân chi tiết**
- each focus area now has an extra deep-dive table:
  - `techCauseDetailsBody`
  - `teacherCauseDetailsBody`
  - `migrateCauseDetailsBody`
- drill-down ticket list now shows:
  - cause group
  - cause detail / evidence line right underneath

## Example detail buckets added
Examples of new detail-level splits:
- Lỗi nền tảng ClassIn / ICL / phòng học
- Lỗi đăng nhập / tài khoản / mật khẩu
- Lỗi mic / cam / âm thanh / thiết bị
- GV vắng / không vào lớp
- GV vào muộn / kết thúc sớm
- Dạy sai giáo trình / sai lộ trình
- Sai lịch / book nhầm / đổi lịch chậm
- Migrate thiếu buổi / cộng thiếu buổi
- Sai package / sai số dư / sai mapping
- Goodwill / xoa dịu / giữ nhiệt phụ huynh
- Ốm / nhập viện / xin nghỉ
- Mất điện / mất mạng / lỗi phía gia đình

## Production verification
Verified directly in `icc-crm-ticket-app` after deploy:
- `/ticket/api/dashboard` now contains `rootCauseDetails`
- `/ticket/api/tickets` now returns `causeDetail` and `causeEvidence`

Sample live ticket payload:
- `684594752`
  - `causeGroup = Migrate / cộng thiếu buổi / sai package`
  - `causeDetail = Migrate thiếu buổi / cộng thiếu buổi`
  - `causeEvidence = lỗi migrate dữ liệu từ BOS lên ICL`
- another sample system case:
  - `causeGroup = Lỗi hệ thống / nền tảng học`
  - `causeDetail = Lỗi nền tảng ClassIn / ICL / phòng học`

## Important note
Current Ticket data can now be sliced much deeper than before, but there is still a large `Khác / cần phân loại thêm` bucket in source data because many rows are still free-text / sparse. So this round improves deep-dive visibility and practical categorization, but does not yet make the taxonomy 100% clean.
