# Deploy Prep Summary

Ngày chuẩn bị: 2026-05-21
Phạm vi: chuẩn bị gói thay đổi local-only để user duyệt trước khi deploy production.

## 1) Mục tiêu thay đổi
Refactor 3 khu vực để đi cùng một mô hình dữ liệu và trải nghiệm:
- Learner Journey / Hành trình học viên
- Buổi học live
- Bài tập LCMS

## 2) Những gì đã hoàn thành
### Data source
- Learner Journey dùng source riêng `learner_journey_students`
- Sync trực tiếp từ Zeus qua SSH tunnel + MySQL
- Trial lesson đã bị loại khỏi source
- `package_groups` lấy theo hierarchy `SpeakWell` từ `tbl_teach_languages`

### Filters
- Checkbox filters cho:
  - nhóm khóa học
  - size lớp
- Filter options trả về kèm count
- Students tab cũng nhận các filter này
- Live và LCMS dùng cùng filter state với Learner Journey

### UI
- Journey / Live / LCMS dùng cùng pattern:
  - learner selector
  - filter panel
  - summary cards
  - session stats
  - progress blocks
  - notes
  - priority queue table
- Đã tối ưu thêm responsive mobile/tablet cho 2 tab Live + LCMS mới
- Đã chỉnh wording business-friendly hơn

## 3) File chính liên quan
### Core frontend
- `CRM-Dashboard/CRM-Dashboard/public/index.html`

### Core backend
- `CRM-Dashboard/CRM-Dashboard/src/app.js`
- `CRM-Dashboard/CRM-Dashboard/src/services/learnerJourneyService.js`
- `CRM-Dashboard/CRM-Dashboard/src/db/database.js`

### Sync / docs local
- `CRM-Dashboard/CRM-Dashboard/src/scripts/syncLearnerJourney.js`
- `CRM-Dashboard/CRM-Dashboard/scripts/sync-learner-journey-from-zeus-tunnel.sh`
- `CRM-Dashboard/CRM-Dashboard/LOCAL_LEARNER_JOURNEY_SYNC.md`
- `CRM-Dashboard/CRM-Dashboard/LOCAL_REVIEW_CHECKLIST.md`
- `CRM-Dashboard/CRM-Dashboard/LOCAL_REVIEW_RESULTS.md`

## 4) Verify hiện tại
- Syntax checks: pass
- Local app boot: pass
- Login local: pass
- `/api/dashboard`: pass
- `/api/students`: pass
- `/api/learner-journey/students`: pass
- Latest learner journey sync: **6375 learners**
- Trial rows in learner source: **0**
- Blank package groups: **0**

## 5) Rủi ro / lưu ý còn lại
- Chưa click-through UI bằng browser automation trong môi trường này
- Cần user duyệt giao diện thật ở local trước khi deploy
- Vì local learner source có thể tăng/giảm nhẹ theo dữ liệu Zeus live, số learner sau sync có thể thay đổi giữa các ngày

## 6) Đề xuất trình tự trước deploy
1. User mở local và duyệt 3 tab:
   - Journey
   - Buổi học live
   - Bài tập LCMS
2. Xác nhận:
   - wording ổn
   - filter behavior đúng
   - grouping course đúng
3. Chốt có deploy production hay chưa
4. Nếu deploy:
   - chạy backup/tag
   - deploy code
   - smoke test login + dashboard + students + learner journey
   - verify 3 tab hiển thị đúng trên production

## 7) Định nghĩa “ready for deploy approval”
Bản này được xem là ready for deploy approval nếu:
- user duyệt local pass
- không phát hiện mismatch nghiệp vụ ở filters/grouping
- không yêu cầu sửa thêm UI lớn
