# REQUESTS.md

## 1. Mục tiêu hiện tại

Hệ thống đang được phát triển theo hướng **1 domain / 2 module**:

- `https://crm.icanwork.vn` = CRM chính
- `https://crm.icanwork.vn/ticket/` = Phân hệ Ticket

Mục tiêu chung:

- theo dõi sức khỏe học tập, hành trình học, lớp live, LCMS, gói học, gia hạn và doanh thu
- hỗ trợ vận hành CSS / manager theo phạm vi phân quyền thật
- dùng Zeus làm nguồn dữ liệu ngày càng nhiều hơn, thay cho logic proxy/legacy khi có thể
- giữ dashboard theo hướng **decision-support**, không chỉ là bảng số liệu

---

## 2. Kiến trúc chốt

### 2.1 CRM root

```text
Google Sheet / dữ liệu lịch sử
        ↓ sync riêng
SQLite nội bộ
        ↓
Dashboard CRM
```

### 2.2 Dữ liệu Zeus

```text
Zeus DB / CSI / learner journey / LCMS / order history
        ↓ service riêng + cache
API nội bộ CRM
        ↓
Dashboard CRM / Hồ sơ học viên / Zeus Analytics
```

### 2.3 Ticket module

```text
Nhiều workbook ticket 2025 / 2026
        ↓ sync riêng
SQLite ticket riêng
        ↓
Ticket dashboard dưới /ticket/
```

Nguyên tắc không đổi:

- không để UI đọc Google Sheet trực tiếp khi người dùng mở trang
- mọi dữ liệu phải đi qua sync job hoặc service backend có cache
- production bắt buộc có login và phân quyền
- không để secret lộ trong repo hoặc log

---

## 3. Các step đã hoàn thành

### Step 1 — Nền tảng dashboard + auth + deploy

Đã hoàn thành:

- CRM chạy production trên `https://crm.icanwork.vn`
- auth + session + user management 4 role:
  - Head
  - CSS Manager
  - CSS Team Leader
  - Staff
- cấp user thật và staff thật theo danh sách CSS
- deploy Docker ổn định, có sync loop, HTTPS, health check

### Step 2 — Restyle UI và shell vận hành

Đã hoàn thành:

- restyle CRM theo `Design_System.html`
- chuyển UX từ filter-heavy sang menu-oriented
- responsive mobile/tablet
- dọn wording business-friendly cho CRM root và Ticket
- đồng bộ shell / typography / card style giữa CRM và Ticket

### Step 3 — Learner universe và workflow học viên

Đã hoàn thành:

- tách learner universe khỏi `dashboard_data`
- learner journey dùng nguồn riêng từ Zeus
- loại trial lesson ở tầng dữ liệu
- nhóm khóa học lấy theo hierarchy thật rooted at `SpeakWell`
- Live / LCMS / Journey cùng learner universe và cùng filter grammar
- hồ sơ học viên, popup chi tiết, purchase history, learning milestones

### Step 4 — Zeus hóa dữ liệu CRM

Đã hoàn thành:

- CSI live lấy trực tiếp từ Zeus DB
- Dashboard / Students dùng population hợp nhất Zeus + legacy
- có làm mờ dữ liệu thiếu coverage
- renewal metrics ước tính từ order history target month
- chuẩn hóa thêm các field như:
  - `learning_pace`
  - `activation_speed`
  - `teacher_disruption_*`
- thêm Zeus Analytics theo hướng action dashboard + drill-down về Students

### Step 5 — LCMS thật

Đã hoàn thành:

- xác minh LCMS thật trong Zeus DB
- nối LCMS summary thật vào student universe
- nối LCMS detail thật vào hồ sơ học viên
- fallback hợp lý khi thiếu mapping / coverage

### Step 6 — Ticket submodule

Đã hoàn thành:

- Ticket chạy dưới `/ticket/`
- update source 2025 SpeakWell-only + 2026 combined multi-source
- wording compare 2025 vs 2026 rõ scope dữ liệu
- thêm learner comparison, risk learners, student drill-down
- thêm teacher-cost heuristic views cho lỗi kỹ thuật / lỗi giáo viên

### Step 7 — Logic filter thời gian và trạng thái

Đã hoàn thành:

- bỏ filter `Quý` khỏi UI CRM root
- nếu đã chọn `Tháng` thì khóa `Từ ngày / Đến ngày`
- thêm filter `Trạng thái học viên = Active / Expired`
- sửa month filter để không bị dính mặc định `2026-04`
- sửa date-range nhiều tháng:
  - target = snapshot tháng mới nhất của từng học viên trong khoảng
  - base = tháng gần nhất trước target
- tab CSI live nay đã được rà và siết lại:
  - tôn trọng role scope thực
  - tôn trọng time filter
  - hỗ trợ `studentStatus` cho Active / Expired

---

## 4. Trạng thái nghiệp vụ hiện tại

CRM root hiện có các cụm chính:

- Tổng quan
- Sức khỏe học viên (CSI)
- Danh sách học viên
- Hồ sơ học viên
- Hành trình học viên
- Buổi học live
- Bài tập LCMS
- Gói học & gia hạn
- Trung tâm điều hành
- Zeus Analytics
- Tài khoản / User Admin / Tiện ích
- Phân hệ Ticket

Các capability chính đã có:

- login + role scope
- learner drill-down
- export PDF + file tabular cơ bản
- popup / hồ sơ học viên / lịch sử mua hàng
- LCMS thật trong learner detail
- action dashboard + preset worklist

---

## 5. Quy tắc bắt buộc

- Không quay lại mô hình đọc trực tiếp Google Sheet trên frontend.
- Không bỏ auth hoặc role scope ở production.
- Không hiển thị dữ liệu thiếu coverage như dữ liệu đầy đủ.
- Không đảo ngược business meaning của target/base, renewal, learner status.
- Ticket phải tiếp tục là submodule dưới `/ticket/`, không tách rời domain.
- CRM root và Ticket phải giữ cùng design language và action pattern.

---

## 6. Các bước còn mở

### Step 8 — Hoàn thiện audit cuối và ổn định vận hành

Các việc còn mở nhưng nhỏ hơn trước:

1. rà nốt wording / caption lẻ tẻ còn sót nếu phát sinh
2. cân nhắc thêm badge/UI rõ hơn cho basis target/base ở nhiều chỗ nếu cần
3. tiếp tục kiểm thử regression cho:
   - month filter
   - date range nhiều tháng
   - CSI scope theo role
   - Active / Expired
4. bổ sung thêm runbook vận hành / backup / smoke test nếu cần formal hơn

### Step 9 — Nâng độ sâu business nếu user yêu cầu thêm

Tùy vòng tiếp theo có thể làm:

- siết sâu hơn logic renewal heuristics
- nâng chất lượng explainability trong Zeus Analytics
- mở rộng Ticket learner matching nếu có source identity tốt hơn
- tăng coverage LCMS / lifecycle / teacher disruption nếu Zeus có rule chuẩn hơn

---

## 7. Cách làm việc cho các vòng tiếp theo

Mỗi thay đổi mới nên đi theo chuỗi:

1. xác định rõ scope nghiệp vụ
2. kiểm tra backend + frontend + dữ liệu live
3. sửa ở local
4. verify bằng API / HTML marker / smoke test
5. deploy production
6. cập nhật summary run và REQUESTS.md nếu thay đổi milestone
