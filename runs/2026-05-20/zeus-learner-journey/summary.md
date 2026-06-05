# Zeus learner journey data check (2026-05-20)

## 1) Nguồn hiện tại của dropdown Learner Journey trong CRM
- Frontend lấy dropdown từ `loadStudentUniverse()` → `GET /api/students?download=all`
- File: `CRM-Dashboard/CRM-Dashboard/public/index.html`
  - `getStudentPackageName()` tại dòng ~1965 đang ưu tiên `lifecycleStatus` trước `renewalProduct`
  - `loadStudentUniverse()` tại dòng ~2364 gọi `/api/students`
- Backend `/api/students` lấy dữ liệu từ SQLite `dashboard_data`, không lấy trực tiếp từ Zeus live DB
  - File: `CRM-Dashboard/CRM-Dashboard/src/app.js`

## 2) Dữ liệu hiện có trong CRM SQLite (`crm.db`)
- Tổng dòng / tổng student: `5054 / 5054`
- `lifecycle_status` chỉ có đúng **2 giá trị**:
  - `1. Mới (Onboarding)`
  - `4. Hết gói (Gap chờ phí)`
- `renewal_product` chỉ có đúng **2 giá trị**:
  - rỗng (`""`) cho `4428` học viên
  - `Onboarding` cho `626` học viên
- `remaining_sessions` hiện tại **âm cho toàn bộ 5054 học viên**
  - min = `-22`
  - max = `-1`
- Kết luận: dữ liệu hiện tại trong CRM **không phải số buổi tồn thật từ Zeus package balance**; nó là dữ liệu đã transform theo model CSI/renewal.

## 3) Dữ liệu Zeus live DB cho lớp 1:1 và 1:2
### Summary
- Học viên 1:1 hoặc 1:2 có bất kỳ lesson row paid/completed order nào: **8461**
- Học viên 1:1 hoặc 1:2 có activity ở trạng thái `1/2/3` (unscheduled/scheduled/completed): **8369**
- CRM hiện tại (`5054`) là **subset** của tập Zeus 1:1/1:2
- Thiếu so với Zeus:
  - so với full 1:1/1:2: **3407** học viên
  - so với active 1:1/1:2: **3315** học viên

### By class size
- `1:1`
  - students: `8114`
  - lesson_rows: `438162`
  - remaining_rows (status 1,2): `224728`
  - completed_rows (status 3): `135273`
- `1:2`
  - students: `845`
  - lesson_rows: `44524`
  - remaining_rows (status 1,2): `24560`
  - completed_rows (status 3): `13970`

### Student-level export generated
- JSON: `runs/2026-05-20/zeus-learner-journey/zeus_1_1_1_2_students.json`
- CSV: `runs/2026-05-20/zeus-learner-journey/zeus_1_1_1_2_students.csv`

Columns:
- `class_size`
- `user_id`
- `student_name`
- `user_email`
- `package_names`
- `purchased_sessions`
- `unscheduled_sessions`
- `scheduled_sessions`
- `completed_sessions`
- `cancelled_sessions`
- `remaining_sessions`
- `first_lesson_starttime`
- `last_lesson_starttime`

## 4) Sample đối chiếu với CRM hiện tại
### Student `497` – Lê Thị Lan Anh
- CRM SQLite hiện tại:
  - `renewal_product = ''`
  - `remaining_sessions = -1`
  - `lifecycle_status = '4. Hết gói (Gap chờ phí)'`
- Zeus live:
  - `class_size = 1:1`
  - `package_names = Business Course | Easy SPEAK - Level 3 | SpeakWell Get Ready 1 | Trial Lesson`
  - `purchased_sessions = 15`
  - `completed_sessions = 13`
  - `cancelled_sessions = 1`
  - `remaining_sessions = 1`

### Student `513` – Nông Minh Tâm
- CRM SQLite hiện tại:
  - `renewal_product = ''`
  - `remaining_sessions = -3`
  - `lifecycle_status = '4. Hết gói (Gap chờ phí)'`
- Zeus live:
  - `class_size = 1:1`
  - `package_names = Starters`
  - `purchased_sessions = 35`
  - `completed_sessions = 35`
  - `remaining_sessions = 0`

## 5) Technical conclusion
1. Dropdown Learner Journey hiện đang đọc từ **CRM SQLite / dashboard_data**, không phải Zeus.
2. Dataset CRM hiện tại chỉ giữ model CSI/renewal rút gọn, nên package/lifecycle bị co lại thành `Onboarding` / `Hết gói`.
3. `remaining_sessions` trong CRM hiện tại **không thể dùng làm số buổi tồn package thật** vì toàn bộ đều âm.
4. Nếu muốn Learner Journey hiển thị đúng:
   - toàn bộ học viên 1:1 và 1:2,
   - tên gói học thật,
   - số buổi đã mua,
   - số buổi còn tồn,
   thì cần đổi nguồn Learner Journey sang **Zeus live / Zeus-synced dataset riêng**, không dùng trực tiếp `dashboard_data` hiện tại.

## 6) Recommended next implementation
- Tạo nguồn dữ liệu Learner Journey riêng từ Zeus với logic:
  - class size = `1:1` / `1:2` theo `tbl_group_classes.grpcls_total_seats`
  - package name = `tbl_teach_languages.tlang_identifier`
  - purchased sessions = `COUNT(tbl_order_lessons)`
  - remaining sessions = `COUNT(status IN (1,2))`
  - completed sessions = `COUNT(status = 3)`
  - cancelled sessions = `COUNT(status = 4)`
- Sau đó cập nhật dropdown/UI learner journey để đọc từ nguồn mới thay vì `dashboard_data` hiện tại.
