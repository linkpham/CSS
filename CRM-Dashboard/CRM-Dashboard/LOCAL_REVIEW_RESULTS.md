# Local Review Results

Ngày review: 2026-05-21
Phạm vi: local-only redesign cho `Learner Journey`, `Buổi học live`, `Bài tập LCMS` trước khi cân nhắc deploy.

## Kết quả tổng quan
- Trạng thái: **PASS có điều kiện**
- Có thể chuẩn bị deploy code sau khi user duyệt UI local
- Chưa deploy production trong bước này

## 1) Chuẩn bị local
- [x] App local khởi động được
- [x] Login `linhpg@hocmai.vn / linh@123` hoạt động
- [x] `GET /api/dashboard` trả `200`
- [x] `GET /api/students` trả `200`
- [x] `GET /api/learner-journey/students` trả `200`
- [x] Syntax check `public/index.html`, `src/app.js`, `src/services/learnerJourneyService.js` đều pass

## 2) Sync learner journey
Lệnh đã chạy:
```bash
bash scripts/sync-learner-journey-from-zeus-tunnel.sh
```
Kết quả gần nhất:
- [x] Sync thành công
- [x] Tổng learner local sau sync: **6375**
- [x] Source API: `learner_journey_students`
- [x] Không còn trial package trong `learner_journey_students`
- [x] Không còn `package_groups` rỗng

Kiểm tra DB:
```json
{"total":6375,"trial_rows":0,"blank_groups":0}
```

## 3) Filter options
- [x] Course group options có dữ liệu thật từ hierarchy SpeakWell
- [x] Class size options có dữ liệu thật

Mẫu API:
```json
{
  "courseGroups": [
    {"value":"Easy Speak for Adults","count":98},
    {"value":"SpeakWell for Teens","count":1617},
    {"value":"SpeakWell Get Ready","count":395},
    {"value":"SpeakWell Hero","count":4635}
  ],
  "classSizes": [
    {"value":"1:1","count":5996},
    {"value":"1:2","count":845}
  ]
}
```

## 4) Learner Journey
- [x] Dùng source riêng `learner_journey_students`
- [x] Checkbox filters hoạt động qua API
- [x] Trial bị loại khỏi source
- [x] Wording business-friendly đã cập nhật
- [~] Cần user click-through local để duyệt cảm quan UI thực tế

## 5) Buổi học live
- [x] Dùng cùng learner universe với Learner Journey
- [x] Dùng cùng checkbox filters course group + class size
- [x] Có learner selector riêng nhưng sync cùng selected learner
- [x] Có summary cards, progress blocks, queue table riêng
- [x] Đã thêm tối ưu responsive mobile/tablet cho layout mới
- [~] Cần user duyệt trực quan local để chốt UI/UX

## 6) Bài tập LCMS
- [x] Dùng cùng learner universe với Learner Journey
- [x] Dùng cùng checkbox filters course group + class size
- [x] Có learner selector riêng nhưng sync cùng selected learner
- [x] Có summary cards, progress blocks, queue table riêng
- [x] Đã thêm tối ưu responsive mobile/tablet cho layout mới
- [~] Cần user duyệt trực quan local để chốt UI/UX

## 7) Students tab
- [x] Nhận filter `courseGroups` + `classSizes`
- [x] API `/api/students` trả thêm `packageGroups`, `classSizes`, session fields từ learner source khi có match
- [x] Verify mẫu:
  - filter `SpeakWell Hero + 1:1`
  - learner total: `4261`
  - students total: `3505`

## 8) Responsive / mobile prep
Đã bổ sung:
- full-width learner selects trên màn nhỏ
- checkbox chips xuống dòng 1 cột trên mobile
- các table mới có `min-width` + vùng scroll ngang
- giảm cỡ typography cho `journey-value` / `package-stat-value` trên mobile
- `stack-card` padding nhỏ hơn trên mobile

## 9) Điểm cần lưu ý trước deploy
- [~] Chưa có browser automation để click-through UI thật do môi trường không có Chrome lane
- [~] Cần user duyệt local bằng mắt trước khi deploy production
- [~] Nên chụp nhanh 3 tab sau khi user duyệt để tránh mismatch kỳ vọng

## Kết luận
Bản local hiện **đủ tốt để bước sang vòng duyệt trước deploy**.

Đề xuất chỉ deploy khi:
1. User duyệt local 3 tab Journey / Live / LCMS
2. User xác nhận wording + filter behavior đúng nghiệp vụ
3. Chạy thêm 1 vòng smoke test sau build/deploy staging hoặc production window
