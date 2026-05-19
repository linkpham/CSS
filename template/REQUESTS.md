# REQUESTS.md

## 0. Vai trò của file này

File này là seed/spec đầu vào để coding agent bắt đầu chạy dự án **CRM-Dashboard** theo chuẩn DUCKMIND:

```text
Seed/spec rõ ràng
        ↓
Agent triển khai thay đổi nhỏ, có kiểm soát
        ↓
Validator hành vi end-to-end
        ↓
Regression + run trace replayable
```

Agent phải ưu tiên xử lý toàn bộ nội dung trong `REQUESTS.md` trước mọi task khác. Nếu còn mục chưa resolve, agent không được chuyển sang task mới trừ khi đã ghi rõ blocker hoặc human xóa/cập nhật file này.

---

## 1. Contract bắt buộc trước khi chạy

Trước khi sửa code, agent phải tạo `runs/<YYYY-MM-DD>/<run-id>/contract.md` và trả lời đủ 5 câu hỏi:

1. **Input**: nguồn dữ liệu, tài liệu, config, credentials, file Excel/Google Sheet nào được cung cấp?
2. **Output**: website, API, dashboard, file build, tài liệu, log, report nào cần tạo?
3. **Failure modes**: dữ liệu thiếu/sai format, credential sai, Google Sheet lỗi, deploy lỗi, phân quyền sai có thể xảy ra thế nào?
4. **Side effects**: file nào được tạo/sửa, command nào được chạy, service/container/database nào bị thay đổi?
5. **Permissions**: agent được đọc/ghi/chạy ở đâu; credential nào được dùng; tuyệt đối không in secret ra log.

Nếu thiếu input bắt buộc, agent phải dừng và hỏi human, không được đoán.

---

## 2. Preflight bắt buộc

Agent phải chạy và ghi log vào `runs/<YYYY-MM-DD>/<run-id>/commands.log`:

```bash
git status
git diff
git log --oneline -5
```

Sau đó kiểm tra các nguồn sau:

| Nguồn | Bắt buộc | Cách xử lý |
|---|---:|---|
| `AGENTS.md` | Có | Đọc và tuân thủ working contract, filesystem-first memory, replayable runs |
| `strongdm.md` | Có nếu repo sử dụng StrongDM | Nếu thiếu nhưng task yêu cầu StrongDM, dừng và hỏi human |
| `CSS/SPW.md` hoặc `CSS/spw.md` | Có | Dùng làm đặc tả nghiệp vụ dashboard CRM/CSS |
| `excels/` | Có cho phân tích schema thật | Nếu thiếu, dừng phần phân tích dữ liệu thật và yêu cầu human cung cấp |
| `docs/specs/*` | Có thể có nhiều file | Nếu có thay đổi dù nhỏ, tạo lại/cập nhật các file trong `scripts/` |
| `conf/` | Chỉ chứa secret local/server | Không commit secret, không ghi credential vào log |

Ghi lại kết quả preflight vào `runs/<YYYY-MM-DD>/<run-id>/artifacts/preflight.md`.

---

## 3. Mục tiêu sản phẩm

Dựa trên dữ liệu trong `excels/` và đặc tả `CSS/SPW.md`, thiết kế và xây dựng website dashboard production-ready cho CRM/CSS với mục tiêu:

- Theo dõi sức khỏe học tập của học viên theo tháng, quý, giai đoạn tùy chọn.
- Đánh giá phục hồi, trượt dốc, giữ nguyên tốt/xấu, mất dữ liệu hoặc nhóm trạng thái gộp theo rule được cung cấp.
- Đo hiệu quả chăm sóc của CSS.
- Theo dõi tương quan giữa sức khỏe học tập, trải nghiệm giáo viên, học dở dang dưới 1/2 giờ và Renewal Rate.
- Dự báo Cash Revenue tháng tiếp theo dựa trên số học viên từng tệp sức khỏe, Renewal Rate và giá trị trung bình/học viên.
- Hỗ trợ quản trị người dùng, phân quyền dữ liệu và vận hành production.

Dashboard phải ưu tiên hỗ trợ ra quyết định, không chỉ hiển thị bảng dữ liệu.

---

## 4. Cấu trúc dự án đích

Agent phải giữ hoặc tạo cấu trúc:

```text
CRM-Dashboard/
├── CRM-Dashboard/
│   ├── public/
│   ├── src/
│   ├── package.json
│   └── ...
├── docs/
│   ├── regression/       # yêu cầu sửa lỗi và regression cases
│   ├── specs/            # specs/seed theo từng phase
│   └── runbooks/         # trạng thái phase, vận hành, incident notes
├── scripts/
│   ├── build.sh          # build/compile web
│   └── deploy.sh         # deploy CRM-Dashboard và run web
├── conf/                 # credentials, không commit secret thật
├── dist/                 # kết quả build nếu có
└── README.md             # human tự điền hướng dẫn dùng
```

Với template gốc, tối thiểu phải có:

```text
template/
├── AGENTS.md
├── REQUESTS.md
├── docs/
│   ├── regression/
│   ├── specs/
│   │   └── SPEC_TEMPLATE.md
│   └── runbooks/
│       └── REGRESSION_TEMPLATE.md
└── scripts/
    ├── build.sh
    └── deploy.sh
```

---

## 5. Yêu cầu nghiệp vụ dashboard

### 5.1 Overview Dashboard

Hiển thị theo giai đoạn được chọn:

- Tổng số học viên.
- Số học viên theo từng nhóm sức khỏe.
- Tỷ lệ phục hồi.
- Tỷ lệ trượt dốc.
- Tỷ lệ giữ nguyên trạng thái.
- Renewal Rate theo từng nhóm sức khỏe.
- Cash Revenue thực tế.
- Cash Revenue dự báo tháng tiếp theo.
- Top rủi ro cần CSS/manager xử lý.

Visualization gợi ý:

- KPI cards cho tổng quan.
- Line chart cho xu hướng theo tháng/quý.
- Stacked bar hoặc heatmap cho phân bổ nhóm sức khỏe.
- Table drill-down cho danh sách học viên cần hành động.

### 5.2 Health Movement Dashboard

Theo dõi chuyển dịch sức khỏe giữa base period và target period:

- Phục hồi.
- Trượt dốc.
- Giữ nguyên tốt.
- Giữ nguyên xấu.
- Mất dữ liệu/không đủ dữ liệu.
- Các trạng thái gộp khác nếu được rule trong `excels/` hoặc spec định nghĩa rõ.

Bộ lọc:

- Tháng/quý/giai đoạn tùy chọn.
- Sản phẩm.
- Giáo viên.
- CSS phụ trách.
- Nhóm học viên.
- Tình trạng học live.
- Health group base/target.

Visualization gợi ý:

- Sankey/funnel hoặc grouped bar cho chuyển dịch.
- Heatmap base → target.
- Timeline theo học viên khi drill-down.

### 5.3 Care Effectiveness Dashboard

Đánh giá hiệu quả chăm sóc:

- Nhóm học viên có chăm sóc.
- Nhóm học viên không có chăm sóc.
- Tỷ lệ phục hồi sau chăm sóc.
- Tỷ lệ trượt dốc sau chăm sóc.
- Renewal Rate của nhóm có chăm sóc.
- Renewal Rate của nhóm không chăm sóc.
- Hiệu quả theo CSS/team/manager.

Visualization gợi ý:

- Bar chart so sánh có chăm sóc vs không chăm sóc.
- Scatter/box plot nếu dữ liệu đủ để xem phân phối.
- Table ranking CSS/team kèm cảnh báo sample size nhỏ.

### 5.4 Renewal Correlation Dashboard

Theo dõi tương quan:

- Renewal Rate theo từng tệp sức khỏe.
- Renewal Rate theo trải nghiệm giáo viên tốt/trung bình/kém.
- Renewal Rate theo nhóm học dở dang dưới 1/2 giờ.
- So sánh RR giữa các nhóm theo sản phẩm, giáo viên, CSS.

Visualization gợi ý:

- Bar chart có confidence/sample size.
- Heatmap tương quan.
- Drill-down danh sách học viên đến hạn gia hạn.

### 5.5 Revenue Forecast Dashboard

Dự báo Cash Revenue tháng tiếp theo:

- Số học viên từng tệp sức khỏe.
- RR lịch sử hoặc RR mặc định của từng tệp.
- Giá trị doanh thu trung bình/học viên.
- Cash Revenue forecast.
- Cho phép chỉnh giả định RR để chạy kịch bản.

Visualization gợi ý:

- KPI forecast tổng.
- Waterfall hoặc stacked bar theo nhóm sức khỏe.
- Scenario table để so sánh base/best/worst case.

---

## 6. Data architecture

Không để dashboard đọc trực tiếp Google Sheet/Excel khi người dùng mở trang.

Kiến trúc bắt buộc:

```text
Google Sheet hoặc Excel nguồn
        ↓ sync/import có log
Raw staging tables/files
        ↓ validate + normalize
Internal database/cache
        ↓ query API có phân quyền
Dashboard UI
```

Yêu cầu:

- Có job đồng bộ/import dữ liệu riêng.
- Có log số dòng đọc, số dòng hợp lệ, số dòng lỗi, số dòng bị bỏ qua.
- Có data quality report cho thiếu mã học viên, trùng mã, sai ngày, sai số tiền, thiếu base/target period.
- Không ghi ngược vào dữ liệu nguồn nếu chưa được cấp quyền rõ.
- Không dùng dữ liệu không có mã học viên để join sức khỏe với doanh thu/gia hạn.

---

## 7. KPI và công thức cần chuẩn hóa

Agent phải tạo hoặc cập nhật spec trong `docs/specs/` cho các định nghĩa sau trước khi code:

- Student count.
- Health group.
- Health movement.
- Recovery rate.
- Decline rate.
- Stable good/bad rate.
- Renewal Rate.
- Cash Revenue actual.
- Cash Revenue forecast.
- Care coverage.
- Care effectiveness uplift.
- Teacher experience group.
- Under-half-hour incomplete learning group.

Mỗi KPI cần có:

- Tên hiển thị.
- Công thức.
- Input fields.
- Filter scope.
- Null/missing-data behavior.
- Ví dụ kiểm thử nhỏ.
- Cảnh báo sample size nếu mẫu quá nhỏ.

---

## 8. UX/UI yêu cầu production-ready

Dashboard phải đạt chất lượng enterprise-grade:

- Responsive desktop/mobile.
- Điều hướng rõ giữa overview và module chuyên sâu.
- Filters dễ hiểu, có trạng thái đang áp dụng.
- Drill-down từ KPI/chart về danh sách học viên.
- Loading, empty, error states đầy đủ.
- Dark/light mode nếu không làm tăng rủi ro triển khai.
- Readability cao: label rõ, đơn vị tiền/tỷ lệ đúng, format ngày nhất quán.
- Không che giấu dữ liệu thiếu; phải hiển thị cảnh báo data quality.
- Ưu tiên insight/action: mỗi màn hình cần trả lời “người quản lý nên làm gì tiếp?”.

---

## 9. Phân quyền người dùng

Tối thiểu có 4 cấp:

| Role | Quyền |
|---|---|
| Head | Xem toàn bộ, quản lý role thấp hơn, cấu hình hệ thống |
| CSS Manager | Xem team dưới quyền, quản lý Team Leader/Staff trong phạm vi |
| CSS Team Leader | Xem nhóm dưới quyền, quản lý Staff trong phạm vi |
| Staff | Xem dữ liệu cá nhân/phạm vi được cấp |

Nguyên tắc:

- Role thấp không được tạo/sửa/xóa role cao hơn.
- API phải enforce quyền, không chỉ ẩn UI.
- Export phải tôn trọng phân quyền dữ liệu.
- Log thao tác quản trị user nếu có chức năng quản trị.

---

## 10. Workflow thực thi theo phase

Agent phải đi tuần tự và ghi trạng thái vào `docs/runbooks/phase_status.md`.

### Phase 0 — Discovery & Contract

- Đọc `AGENTS.md`, `REQUESTS.md`, `docs/specs/*`, `CSS/SPW.md`, `strongdm.md` nếu có.
- Kiểm tra `excels/` và lập inventory schema.
- Tạo run trace trong `runs/<date>/<run-id>/`.
- Ghi blockers nếu thiếu file.

### Phase 1 — Data Profiling & KPI Spec

- Phân tích workbook/sheet/file trong `excels/`.
- Lập data dictionary.
- Định nghĩa join keys, date fields, money fields, enum fields.
- Viết/cập nhật specs KPI.
- Tạo fixture nhỏ không chứa secret.

### Phase 2 — Data Pipeline

- Xây sync/import job.
- Validate dữ liệu.
- Normalize vào database/cache nội bộ.
- Tạo data quality report.
- Test lỗi dữ liệu thiếu/trùng/sai format.

### Phase 3 — Dashboard Foundation

- Build layout, navigation, theme, filters.
- Tạo overview dashboard.
- Tạo API có phân quyền.
- Loading/empty/error states.

### Phase 4 — Module Dashboards

- Health Movement.
- Care Effectiveness.
- Renewal Correlation.
- Revenue Forecast.
- Drill-down tables.

### Phase 5 — User, Auth & Permissions

- Login/session.
- Role hierarchy.
- User management.
- Permission tests cho UI/API/export.

### Phase 6 — Export, Reporting & Observability

- Export CSV/Excel/PDF nếu được yêu cầu.
- Monitoring/logging cơ bản.
- Backup database/cache.
- Runbook vận hành.

### Phase 7 — Production Build & Deploy

- Chạy `scripts/build.sh`.
- Chạy validator/scenario/e2e.
- Chạy `scripts/deploy.sh` khi có quyền.
- Gửi report Discord theo `config/discord.cnf` nếu file config thật tồn tại.

---

## 11. Validator và regression bắt buộc

Trước khi đóng task, agent phải có validator hành vi:

- Import/sync một fixture hoặc mẫu dữ liệu thật đã được ẩn thông tin nhạy cảm.
- Tính đúng KPI trên sample có expected output.
- Render dashboard không lỗi.
- Filters đổi kết quả đúng.
- Drill-down khớp với KPI tổng.
- Role thấp không xem được dữ liệu ngoài phạm vi.
- Export không vượt quyền.
- Empty/missing/error states hiển thị rõ.

Khi sửa bug:

- Tạo hoặc cập nhật file trong `docs/regression/`.
- Mô tả bug gốc, điều kiện tái hiện, expected behavior sau fix, test data.
- Không đóng task nếu regression chưa được chạy.

---

## 12. Scripts bắt buộc

Nếu bất kỳ file nào trong `docs/specs/` thay đổi, agent phải review và cập nhật lại scripts tương ứng:

- `scripts/build.sh`: cài dependency nếu cần, build/compile/test smoke.
- `scripts/deploy.sh`: deploy hoặc hướng dẫn deploy an toàn, kiểm tra env/config, gửi report Discord nếu cấu hình có thật.

Scripts phải:

- Dùng `set -euo pipefail`.
- Không in secret.
- Fail rõ khi thiếu config.
- Có output đủ để ghi vào run trace.

---

## 13. Báo cáo hoàn tất

Sau mỗi phase/task:

1. Cập nhật `docs/runbooks/phase_status.md`.
2. Ghi `runs/<date>/<run-id>/result.json`.
3. Nếu có Discord config thật, gửi report đến channel theo `config/discord.cnf`.
4. Tạo git commit theo Angular/Commitizen:

```bash
git commit -m "feat(dashboard): implement <scope>

- <task completed>
- <validator evidence>
- <phase complete, next task>

Refs: docs/runbooks/phase_status.md"
```

Không commit secret, credential, file dữ liệu nhạy cảm hoặc log chứa token.

---

## 14. Blocker hiện cần human xác nhận nếu dùng template này

Trong repo hiện tại có thể chưa có:

- `strongdm.md`
- `excels/`
- credentials thật trong `conf/`
- `config/discord.cnf`

Nếu task yêu cầu dùng các nguồn trên mà file không tồn tại, agent phải ghi blocker vào run trace và hỏi human cung cấp, thay vì tự tạo dữ liệu giả rồi coi là dữ liệu thật.
