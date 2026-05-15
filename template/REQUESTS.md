# REQUESTS.md

## Phạm vi hiện tại

Chỉ sử dụng kiến trúc đã được chuyển đổi sang mô hình:

```text
Google Sheet / Data_Model
        ↓
Sync job
        ↓
SQLite Database
        ↓
Backend API + Socket.io
        ↓
Dashboard UI + Filters
```

Không quay lại mô hình cũ là Dashboard gọi trực tiếp Google Sheets mỗi lần người dùng mở trang.

---

## Trạng thái đã hoàn thành

### 1. Data source

- Nguồn dữ liệu Google Sheet hiện tại: tab `Data_Model`.
- Không đọc các sheet cũ theo index.
- Không đọc các cột cũ như:
  - `Cut off date`
  - `Period_Month`
  - `Điểm sức khỏe`
  - `Phân loại sức khỏe`
- Data model hiện tại đọc theo các cột thật của `Data_Model`, gồm:
  - `Student_ID`
  - `Tên Học viên`
  - `Email`
  - `SĐT`
  - `CSS`
  - `Score_Target`
  - `Score_Base`
  - `MoM/QoQ_Variance`
  - `Phân loại Target`
  - `Phân loại Base`
  - `Nhóm`
  - `Tỉ lệ gián đoạn do GV`
  - `Tỉ lệ học dở`
  - `Tốc độ kích hoạt`
  - `Trạng thái gia hạn`
  - `Doanh thu gia hạn`
  - `Sản phẩm gia hạn chi tiết`
  - `Số buổi còn lại`
  - `Trạng thái vòng đời`
  - `Điểm sức khỏe quản trị`
  - `Nhịp độ học tập`
  - `Gián đoạn do GV  (tích lũy)`

---

### 2. Google Sheet service

File chính:

```text
src/services/gsheetService.js
```

Đã cập nhật để:

- Xác thực Google Sheets bằng Service Account + `google-spreadsheet` + `google-auth-library`.
- Chỉ đọc sheet theo title `Data_Model`.
- Map dữ liệu thô sang object nghiệp vụ:

```js
{
  student: {},
  health: {},
  movement: {},
  operation: {},
  renewal: {}
}
```

- Có parser số và phần trăm:
  - `parseNumber()`
  - `parsePercent()`

Yêu cầu duy trì:

- Không gọi `getSheetData()` trực tiếp trong request dashboard.
- Chỉ dùng `getSheetData()` trong sync job.

---

### 3. Sync job

File chính:

```text
src/scripts/sync.js
```

Đã chuyển Google Sheet thành job đồng bộ riêng:

```text
Google Sheet Data_Model → SQLite dashboard_data
```

Job thực hiện:

- Đọc dữ liệu từ Google Sheet tab `Data_Model`.
- Xóa dữ liệu cũ trong bảng `dashboard_data`.
- Insert lại toàn bộ dữ liệu mới.
- Ghi `synced_at` cho mỗi lần đồng bộ.
- Có thể chạy thủ công bằng lệnh:

```bash
cd /mnt/f/code/strongdm-main/CRM-Dashboard/CRM-Dashboard
node src/scripts/sync.js
```

Kết quả sync đã kiểm chứng:

```text
5054 rows
```

Lưu ý quan trọng:

- Sync job chạy tách khỏi web process.
- Không import sync job vào web server để tránh web bị treo nếu Google API chậm.
- Endpoint `/api/sync` hiện chỉ trả thông báo hướng dẫn chạy sync riêng, không tự chạy Google sync trong web process.

---

### 4. Database

File chính:

```text
src/db/database.js
```

Đã dùng SQLite làm database cache nội bộ.

Bảng chính:

```sql
dashboard_data
```

Các nhóm dữ liệu lưu trong DB:

- Period / Time:
  - `cut_off_date`
  - `period_month`
  - `period_quarter`
  - `period_year`
  - `period_week`
- Student:
  - `student_id`
  - `student_name`
  - `email`
  - `phone`
  - `css`
- Health:
  - `score_target`
  - `score_base`
  - `variance`
  - `target_category`
  - `base_category`
  - `management_health_score`
  - `learning_pace`
- Movement:
  - `movement_group`
- Operation:
  - `teacher_disruption_rate`
  - `unfinished_rate`
  - `activation_speed`
  - `teacher_disruption_cumulative`
- Renewal:
  - `renewal_status`
  - `renewal_revenue`
  - `renewal_product`
  - `remaining_sessions`
  - `lifecycle_status`
- Sync:
  - `synced_at`

Điều chỉnh quan trọng:

- Không đặt SQLite DB runtime trên `/mnt/f` để tránh lỗi lock/hang IO trong WSL/NTFS.
- DB runtime được đặt ở filesystem Linux local:

```text
/home/linhpg/.crm-dashboard/crm.db
```

Có thể override bằng biến môi trường:

```bash
CRM_DB_DIR=/path/to/db
CRM_DB_PATH=/path/to/crm.db
```

---

### 5. Backend dashboard server

File chính:

```text
src/app.js
```

Backend hiện chỉ đọc từ SQLite, không đọc Google Sheet trực tiếp.

Luồng xử lý:

```text
SQLite dashboard_data
        ↓
mapDbRow()
        ↓
classifyHealthMovement()
        ↓
applyFilters()
        ↓
calculateComprehensiveMetrics()
        ↓
API / Socket.io payload
```

Endpoints hiện có:

```http
GET /health
GET /api/dashboard
POST /api/sync
```

Trong đó:

- `GET /health`: kiểm tra server sống.
- `GET /api/dashboard`: trả payload dashboard từ SQLite.
- `POST /api/sync`: không chạy sync trực tiếp; trả hướng dẫn chạy `node src/scripts/sync.js`.

Socket.io:

- Emit sự kiện:

```text
dataUpdate
```

- Nhận filter từ frontend qua:

```text
setFilters
```

Backend có cache RAM để tăng tốc:

```js
DATA_CACHE_TTL_MS = 15000
```

Mục tiêu cache:

- Không query SQLite và map 5054 dòng liên tục.
- Tăng tốc F5 dashboard.
- Tăng tốc apply filter.
- Giảm lag khi socket update.

---

### 6. Analytics engine

File chính:

```text
src/services/analyticsService.js
```

Đã dựng lại logic theo `Data_Model`.

Các hàm chính:

- `normalizeMovementGroup(group)`
- `classifyHealthMovement(data)`
- `applyFilters(data, filters)`
- `calculateComprehensiveMetrics(data)`
- `getFilterOptions(data)`
- `parseDateValue(value)`

Dashboard metrics hiện hỗ trợ:

#### Overview

- Tổng học viên.
- Điểm Target trung bình.
- Điểm Base trung bình.
- Tỷ lệ phục hồi / cải thiện.
- Tỷ lệ trượt dốc.
- Tỷ lệ giữ nguyên.
- Renewal Rate.
- Cash Revenue.
- Forecast Revenue.

#### Health Movement

Mapping nhóm chuyển dịch theo `Nhóm`:

- `1.` và `2.` → `Phục hồi / cải thiện`
- `4.` và `5.` → `Trượt dốc`
- `3a.` → `Giữ nguyên tốt`
- `3b.` và `6.` → `Giữ nguyên xấu`
- `7.`, `8.`, `9.` → `Mới / không đủ base`

Đồng thời giữ lại thống kê nhóm chi tiết theo nguyên văn `Nhóm`.

#### Care / Operation

- CSS distribution.
- Tỷ lệ học dở trung bình.
- Tỷ lệ gián đoạn do GV trung bình.

#### Renewal Correlation

- Renewal status counts.
- Renewal Rate theo từng phân loại sức khỏe Target.
- Lifecycle status counts.

#### Forecast

- Forecast theo từng tệp sức khỏe Target.
- RR giả định mặc định:
  - Khỏe mạnh: 35%
  - Cần chú ý: 22%
  - Báo động: 10%
- Forecast dùng average renewal revenue nếu có; fallback 5.000.000đ nếu chưa có doanh thu gia hạn.

---

### 7. Frontend dashboard

File chính:

```text
public/index.html
```

Đã dựng lại dashboard dùng dữ liệu từ API/Socket, không đọc Google Sheet.

Thành phần hiện có:

#### Filters động

Filter options được backend sinh từ toàn bộ DB cache.

Bộ lọc thời gian đã được bổ sung:

- Quý.
- Tháng.
- Từ ngày.
- Đến ngày.

Lưu ý: filter `Năm` đã được bỏ khỏi UI/backend filter theo yêu cầu hiện tại.

Các cột thời gian được hỗ trợ nếu có trong `Data_Model`:

- `Cut off date` / `Ngày` / `Date`
- `Period_Month` / `Tháng` / `Month`
- `Period_Quarter` / `Quý` / `Quarter`
- `Period_Year` / `Năm` / `Year`
- `Period_Week` / `Tuần` / `Week`

Nếu `Data_Model` hiện tại chưa có các cột thời gian này, dashboard vẫn chạy bình thường và filter thời gian không làm mất dữ liệu.

Các filter nghiệp vụ hiện có:

- Nhóm Health Movement gộp.
- CSS / người chăm sóc.
- Nhóm chuyển dịch chi tiết.
- Phân loại Target.
- Phân loại Base.
- Trạng thái gia hạn.
- Sản phẩm gia hạn.
- Trạng thái vòng đời.

Frontend gửi filter bằng Socket.io:

```js
socket.emit('setFilters', filters)
```

#### KPI cards

- Tổng học viên.
- Điểm Target TB.
- Tỷ lệ phục hồi / cải thiện.
- Tỷ lệ trượt dốc.
- Renewal Rate.
- Cash Revenue.
- Forecast Revenue.
- Số dòng sau lọc.

#### Charts

- Health Movement - 6 nhóm gộp.
- Phân loại sức khỏe Target.
- Nhóm chuyển dịch chi tiết.
- Renewal Status.

#### Tables

- Forecast theo tệp sức khỏe.
- Renewal Rate theo sức khỏe.

#### Export PDF

Đã bổ sung tính năng xuất dashboard hiện tại ra file PDF trực tiếp trên frontend.

Cách dùng:

- Người dùng chọn filter mong muốn.
- Nhấn nút `Xuất PDF` trên thanh filter.
- Hệ thống capture dashboard hiện tại, tự chia nhiều trang A4 và tải file `.pdf`.

Thư viện frontend đang dùng qua CDN:

- `html2canvas@1.4.1`
- `jspdf@2.5.1`

Đặc điểm triển khai:

- Export theo đúng trạng thái dashboard đang xem, bao gồm KPI, charts, tables và filter context.
- Không gọi Google Sheet trực tiếp khi export PDF.
- Không tạo PDF trong backend.
- Không ghi file PDF vào server.
- File PDF được tạo và tải xuống ở trình duyệt người dùng.
- Nếu thư viện CDN không tải được, fallback sang `window.print()` để người dùng có thể `Save as PDF` bằng trình duyệt.
- Các nút thao tác có class `.no-export` sẽ bị ẩn khi export/print.

File chính:

```text
public/index.html
```

Các hàm chính:

```js
exportDashboardPDF()
buildPdfFileName()
```

---

### 8. Tối ưu hiệu năng và layout

Đã xử lý vấn đề load chậm và biểu đồ quá dài.

#### Backend

- Thêm RAM cache trong `src/app.js`.
- TTL mặc định: 15 giây.
- Không query DB liên tục nếu dữ liệu cache còn hạn.

#### Frontend

- Tắt animation Chart.js:

```js
animation: false
```

- Giới hạn chiều cao chart cards:
  - Chart thường: 380px.
  - Compact chart: 340px.
  - Wide chart: 420px.

- Bọc canvas trong `.chart-box` để không kéo dài layout.
- Biểu đồ nhóm chuyển dịch chi tiết đổi sang horizontal bar.
- Rút gọn label dài trên trục, giữ label đầy đủ trong tooltip.
- Giới hạn số item hiển thị trong chart:
  - Movement: 8.
  - Target health: 8.
  - Detailed group: 10.
  - Renewal: 8.

---

## Cách vận hành hiện tại

### 1. Sync dữ liệu từ Google Sheet vào Database

Chạy khi cần cập nhật dữ liệu:

```bash
cd /mnt/f/code/strongdm-main/CRM-Dashboard/CRM-Dashboard
node src/scripts/sync.js
```

### 2. Chạy dashboard web

Chạy foreground, giữ terminal mở:

```bash
cd /mnt/f/code/strongdm-main/CRM-Dashboard/CRM-Dashboard
./scripts/run.sh
```

Hoặc:

```bash
cd /mnt/f/code/strongdm-main/CRM-Dashboard/CRM-Dashboard
HOST=0.0.0.0 PORT=3000 node src/app.js
```

Mở trình duyệt:

```text
http://127.0.0.1:3000
```

Nếu browser chạy ở Windows/host mà `127.0.0.1` không vào được, dùng WSL IP:

```bash
hostname -I
```

Sau đó mở:

```text
http://<WSL_IP>:3000
```

---

## Những việc không được làm trong phase hiện tại

- Không để dashboard query Google Sheets trực tiếp khi user mở web.
- Không import `google-spreadsheet` trong `src/app.js`.
- Không import `src/scripts/sync.js` vào web process để chạy auto sync.
- Không đọc sheet bằng index như `sheetsByIndex[0]`.
- Không dùng lại schema cột cũ không thuộc `Data_Model`.
- Không đặt SQLite DB runtime trên `/mnt/f` nếu chạy trong WSL.

---

## Việc nên làm tiếp theo

1. Thêm script export CSV/Excel từ dữ liệu đã lọc.
2. Thêm runbook vận hành sync định kỳ bằng cron hoặc Windows Task Scheduler.
3. Thêm bảng drill-down danh sách học viên theo filter hiện tại.
4. Tách frontend thành nhiều tab:
   - Overview
   - Health Movement
   - Care Effectiveness
   - Renewal Correlation
   - Revenue Forecast
5. Thêm kiểm thử regression cho:
   - Sheet `Data_Model` đổi tên cột.
   - DB rỗng.
   - Sync thất bại.
   - Filter không có kết quả.
