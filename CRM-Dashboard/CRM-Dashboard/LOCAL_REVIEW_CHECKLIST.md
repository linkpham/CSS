# Local Review Checklist

Phạm vi: bản local-only của CRM Dashboard trước khi cân nhắc deploy production.

## 1) Chuẩn bị local
- [ ] Chạy app local thành công
- [ ] Login được bằng `linhpg@hocmai.vn / linh@123`
- [ ] Không còn lỗi refresh kiểu `HTTP 404`
- [ ] `GET /api/dashboard` trả `200`
- [ ] `GET /api/students` trả `200`
- [ ] `GET /api/learner-journey/students` trả `200`

## 2) Sync dữ liệu learner journey
- [ ] Chạy `bash scripts/sync-learner-journey-from-zeus-tunnel.sh` thành công
- [ ] Nguồn trả về là `learner_journey_students`
- [ ] Tổng learner local khớp kỳ vọng gần nhất sau loại trial
- [ ] Không còn package trial trong learner journey
- [ ] Có các nhóm khóa học lấy từ hierarchy SpeakWell

## 3) Learner Journey
- [ ] Tab Learner Journey mở bình thường
- [ ] Dropdown học viên load được dữ liệu thật
- [ ] Checkbox filter nhóm khóa học hiển thị count
- [ ] Checkbox filter size lớp hiển thị count
- [ ] Chọn filter làm thay đổi danh sách learner đúng
- [ ] Nút `Bỏ chọn` reset filter đúng
- [ ] Card gói học / sức khỏe / readiness hiển thị đúng dữ liệu
- [ ] Progress bars và notes hiển thị không lỗi

## 4) Buổi học live
- [ ] Tab Buổi học live dùng cùng learner universe với Learner Journey
- [ ] Dropdown học viên đồng bộ đúng với learner đang lọc
- [ ] Checkbox filter giống Learner Journey
- [ ] Filter đang chọn ở Journey phản ánh sang Live
- [ ] Card chất lượng live hiển thị GV disruption, unfinished, remaining sessions đúng
- [ ] Progress sections hiển thị đúng
- [ ] Bảng `Danh sách ưu tiên can thiệp live` load được

## 5) Bài tập LCMS
- [ ] Tab Bài tập LCMS dùng cùng learner universe với Learner Journey
- [ ] Dropdown học viên đồng bộ đúng với learner đang lọc
- [ ] Checkbox filter giống Learner Journey
- [ ] Card bám học / target-base / intervention hiển thị đúng
- [ ] Progress sections hiển thị đúng
- [ ] Bảng `Danh sách ưu tiên can thiệp LCMS` load được

## 6) Students tab
- [ ] Students tab nhận filter course group + class size
- [ ] Search + filter cùng hoạt động ổn định
- [ ] Export CSV hoạt động
- [ ] Export Excel hoạt động
- [ ] Dữ liệu packageGroups / classSizes đi qua API đúng

## 7) Kiểm tra nhanh API gợi ý
```bash
curl -sS 'http://127.0.0.1:3000/api/learner-journey/students?courseGroups=SpeakWell%20Hero&classSizes=1:1' \
  -H 'Authorization: Bearer <TOKEN>'

curl -sS 'http://127.0.0.1:3000/api/students?courseGroups=SpeakWell%20Hero&classSizes=1:1&page=1&pageSize=5' \
  -H 'Authorization: Bearer <TOKEN>'
```

## 8) Quyết định trước production
Chỉ cân nhắc deploy khi tất cả mục dưới đây đều đạt:
- [ ] UI 3 tab Journey / Live / LCMS được user duyệt
- [ ] Filter behavior được user xác nhận đúng nghiệp vụ
- [ ] Không còn learner trial trong source
- [ ] Local smoke test pass
- [ ] Có xác nhận rõ ràng từ user là cho phép deploy
