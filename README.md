# CSS SPW / CSI CRM Dashboard

Dashboard theo dõi sức khỏe học tập học viên, chuyển dịch trạng thái sức khỏe, hiệu quả chăm sóc, tương quan gia hạn và dự báo doanh thu dựa trên dữ liệu Google Sheets.

Dự án hiện sử dụng kiến trúc ổn định:

```text
Google Sheet / Data_Model
        ↓
Sync job
        ↓
SQLite Database
        ↓
Backend API + Socket.io
        ↓
Dashboard UI + Filters + PDF Export
```

> Dashboard **không đọc Google Sheets trực tiếp khi người dùng mở web**. Dữ liệu được đồng bộ trước vào SQLite để tăng tốc, giảm lỗi quota/permission và giúp dashboard ổn định hơn.

---

## 1. Tính năng chính

### Dashboard nghiệp vụ

- Tổng số học viên.
- Điểm sức khỏe Target/Base trung bình.
- Tỷ lệ phục hồi / cải thiện.
- Tỷ lệ trượt dốc.
- Tỷ lệ giữ nguyên.
- Renewal Rate.
- Cash Revenue.
- Forecast Revenue.
- Health Movement theo nhóm gộp.
- Health Movement theo nhóm chi tiết.
- Phân loại sức khỏe Target/Base.
- Renewal status và Renewal Rate theo nhóm sức khỏe.
- Chỉ số vận hành/chăm sóc:
  - CSS distribution.
  - Tỷ lệ học dở trung bình.
  - Tỷ lệ gián đoạn do giáo viên trung bình.

### Bộ lọc

Dashboard hỗ trợ filter động từ dữ liệu trong DB:

- Quý.
- Tháng.
- Từ ngày.
- Đến ngày.
- Nhóm Health Movement gộp.
- CSS / người chăm sóc.
- Nhóm chuyển dịch chi tiết.
- Phân loại Target.
- Phân loại Base.
- Trạng thái gia hạn.
- Sản phẩm gia hạn.
- Trạng thái vòng đời.

### Xuất báo cáo

- Xuất dashboard hiện tại ra PDF trực tiếp trên trình duyệt.
- PDF giữ trạng thái filter hiện tại.
- Tự chia nhiều trang A4 nếu dashboard dài.
- Không tạo file PDF trên server.

---

## 2. Công nghệ sử dụng

### Backend

- Node.js
- Express
- Socket.io
- SQLite
- google-spreadsheet
- google-auth-library

### Frontend

- HTML/CSS/JavaScript thuần
- Chart.js
- html2canvas
- jsPDF

### Database

SQLite runtime được lưu ở filesystem Linux local:

```text
/home/linhpg/.crm-dashboard/crm.db
```

Không đặt DB runtime trên `/mnt/f` trong WSL để tránh lỗi lock/hang IO.

Có thể override bằng biến môi trường:

```bash
CRM_DB_DIR=/path/to/db
CRM_DB_PATH=/path/to/crm.db
```

---

## 3. Cấu trúc thư mục quan trọng

```text
strongdm-main/
├── AGENTS.md
├── CSS/
│   └── SPW.md
├── CRM-Dashboard/
│   ├── conf/
│   │   └── credentials.json          # Không commit
│   ├── docs/
│   │   └── runbooks/
│   │       └── phase_status.md
│   └── CRM-Dashboard/
│       ├── package.json
│       ├── public/
│       │   └── index.html            # Dashboard UI
│       ├── scripts/
│       │   └── run.sh                # Chạy web server
│       ├── src/
│       │   ├── app.js                # Express + Socket.io API server
│       │   ├── db/
│       │   │   └── database.js       # SQLite schema/migration
│       │   ├── scripts/
│       │   │   └── sync.js           # Google Sheet → SQLite sync job
│       │   └── services/
│       │       ├── analyticsService.js
│       │       └── gsheetService.js
│       └── template/
│           └── REQUESTS.md           # Ghi trạng thái/kiến trúc hiện tại
├── init.md
└── template/
    └── REQUESTS.md                   # Bản đồng bộ cho coding agent
```

---

## 4. Chuẩn bị môi trường

### 4.1. Cài Node dependencies

```bash
cd /mnt/f/code/strongdm-main/CRM-Dashboard/CRM-Dashboard
npm install
```

Nếu thiếu SQLite:

```bash
npm install sqlite3
```

---

## 5. Cấu hình Google Sheets

### 5.1. Google Sheet nguồn

Dashboard đọc dữ liệu từ tab:

```text
Data_Model
```

Spreadsheet ID hiện tại được cấu hình trong:

```text
src/scripts/sync.js
```

Mặc định:

```text
1t46BJhDlgB8BYRlx29x5GeB-Gd3A4Jolzm87OZhYdAQ
```

Có thể override bằng biến môi trường:

```bash
SPREADSHEET_ID=<google_sheet_id> SHEET_NAME=Data_Model node src/scripts/sync.js
```

### 5.2. Service Account credentials

File credentials cần đặt tại:

```text
/mnt/f/code/strongdm-main/CRM-Dashboard/conf/credentials.json
```

File này **không được commit lên GitHub**.

Google Sheet phải được share quyền Viewer cho email service account trong `credentials.json`.

---

## 6. Data model hiện tại

Tab `Data_Model` cần có các cột nghiệp vụ chính:

```text
Student_ID
Tên Học viên
Email
SĐT
CSS
Score_Target
Score_Base
MoM/QoQ_Variance
Phân loại Target
Phân loại Base
Nhóm
Tỉ lệ gián đoạn do GV
Tỉ lệ học dở
Tốc độ kích hoạt
Trạng thái gia hạn
Doanh thu gia hạn
Sản phẩm gia hạn chi tiết
Số buổi còn lại
Trạng thái vòng đời
Điểm sức khỏe quản trị
Nhịp độ học tập
Gián đoạn do GV  (tích lũy)
```

### Cột thời gian được hỗ trợ nếu có

Nếu muốn filter theo thời gian hoạt động đầy đủ, `Data_Model` có thể bổ sung một hoặc nhiều cột sau:

| Ý nghĩa | Tên cột hỗ trợ |
|---|---|
| Ngày | `Cut off date`, `Ngày`, `Date` |
| Tháng | `Period_Month`, `Tháng`, `Month` |
| Quý | `Period_Quarter`, `Quý`, `Quarter` |
| Năm | `Period_Year`, `Năm`, `Year` |
| Tuần | `Period_Week`, `Tuần`, `Week` |

Lưu ý: filter `Năm` hiện đã bị bỏ khỏi UI theo yêu cầu, nhưng DB vẫn lưu `period_year` để phục vụ mở rộng sau.

---

## 7. Cách vận hành

### 7.1. Đồng bộ dữ liệu từ Google Sheet vào SQLite

Chạy khi cần cập nhật dữ liệu:

```bash
cd /mnt/f/code/strongdm-main/CRM-Dashboard/CRM-Dashboard
node src/scripts/sync.js
```

Khi thành công sẽ thấy log dạng:

```text
[sync] Starting Google Sheet sync: Data_Model
[sync] Completed: 5054 rows at <timestamp>
```

### 7.2. Chạy dashboard web

Chạy foreground và giữ terminal mở:

```bash
cd /mnt/f/code/strongdm-main/CRM-Dashboard/CRM-Dashboard
./scripts/run.sh
```

Hoặc:

```bash
cd /mnt/f/code/strongdm-main/CRM-Dashboard/CRM-Dashboard
HOST=0.0.0.0 PORT=3000 node src/app.js
```

Khi server chạy đúng:

```text
Server running on http://127.0.0.1:3000
Server bound to 0.0.0.0:3000
```

Mở trình duyệt:

```text
http://127.0.0.1:3000
```

Nếu dùng WSL và browser trên Windows không vào được `127.0.0.1`, lấy IP bằng:

```bash
hostname -I
```

Rồi mở:

```text
http://<WSL_IP>:3000
```

---

## 8. API hiện có

### Health check

```http
GET /health
```

Trả về trạng thái server.

### Dashboard data

```http
GET /api/dashboard
```

Trả về payload dashboard từ SQLite.

Có thể truyền query filter, ví dụ:

```http
GET /api/dashboard?healthMovementGroup=Trượt%20dốc
GET /api/dashboard?css=huyenhk
GET /api/dashboard?targetCategory=3.%20Khỏe%20mạnh%20(85-100)
```

### Sync endpoint

```http
POST /api/sync
```

Endpoint này **không chạy sync trực tiếp**. Sync Google Sheet được tách khỏi web process để tránh web bị treo nếu Google API chậm.

Muốn sync, chạy:

```bash
node src/scripts/sync.js
```

---

## 9. Socket.io realtime

Frontend nhận dashboard payload qua event:

```text
dataUpdate
```

Frontend gửi filter qua event:

```text
setFilters
```

Ví dụ payload filter:

```js
{
  quarter: 'Q1',
  month: '1',
  fromDate: '2026-01-01',
  toDate: '2026-01-31',
  healthMovementGroup: 'Trượt dốc',
  css: 'huyenhk',
  group: '5. Tụt dốc nghiêm trọng',
  targetCategory: '1. Báo động (<60)',
  baseCategory: '3. Khỏe mạnh (85-100)',
  renewalStatus: 'Bán mới',
  product: 'Onboarding',
  lifecycleStatus: '1. Mới (Onboarding)'
}
```

---

## 10. Health Movement mapping

Cột nguồn:

```text
Nhóm
```

Mapping nhóm gộp:

| Giá trị bắt đầu bằng | Nhóm gộp |
|---|---|
| `1.` hoặc `2.` | Phục hồi / cải thiện |
| `4.` hoặc `5.` | Trượt dốc |
| `3a.` | Giữ nguyên tốt |
| `3b.` hoặc `6.` | Giữ nguyên xấu |
| `7.`, `8.`, `9.` | Mới / không đủ base |

Dashboard vẫn giữ thêm thống kê theo nhóm chi tiết gốc.

---

## 11. Export PDF

Dashboard có nút:

```text
Xuất PDF
```

Cách dùng:

1. Chọn filter cần xem.
2. Nhấn **Áp dụng**.
3. Nhấn **Xuất PDF**.
4. Trình duyệt tải file PDF về máy.

File PDF được tạo ở frontend bằng:

- html2canvas
- jsPDF

Nếu CDN không tải được, hệ thống fallback sang `window.print()` để người dùng chọn **Save as PDF**.

---

## 12. Hiệu năng và layout

Đã tối ưu:

- Backend cache RAM 15 giây:

```text
DATA_CACHE_TTL_MS=15000
```

- Không query SQLite/map 5054 dòng liên tục.
- Chart.js tắt animation.
- Biểu đồ được giới hạn chiều cao.
- Biểu đồ nhóm chi tiết dùng horizontal bar.
- Label dài được rút gọn ở trục, giữ đầy đủ trong tooltip.

Có thể chỉnh TTL:

```bash
DATA_CACHE_TTL_MS=30000 HOST=0.0.0.0 PORT=3000 node src/app.js
```

---

## 13. Bảo mật

Không commit các file sau:

```text
CRM-Dashboard/conf/credentials.json
*.db
node_modules/
.env
XLS/*.xlsx
```

Đã cấu hình trong `.gitignore`.

File private key SSH/GitHub và Google credentials tuyệt đối không chia sẻ công khai.

---

## 14. Troubleshooting

### Không vào được web

Kiểm tra server có chạy không:

```bash
curl http://127.0.0.1:3000/health
```

Nếu dùng WSL, thử IP:

```bash
hostname -I
```

Rồi mở:

```text
http://<WSL_IP>:3000
```

### Dashboard không có dữ liệu

Kiểm tra DB đã sync chưa:

```bash
cd /mnt/f/code/strongdm-main/CRM-Dashboard/CRM-Dashboard
node src/scripts/sync.js
```

Sau đó chạy lại web.

### Google Sheet lỗi permission

Kiểm tra:

- Google Sheets API đã enable.
- Sheet đã share Viewer cho `client_email` trong `credentials.json`.
- Đúng tab `Data_Model`.

### Filter thời gian trống

Nguyên nhân thường là `Data_Model` chưa có cột thời gian được hỗ trợ.

Bổ sung các cột như:

```text
Period_Month
Period_Quarter
Cut off date
```

Rồi chạy lại:

```bash
node src/scripts/sync.js
```

---

## 15. Lộ trình tiếp theo

Các hạng mục nên triển khai tiếp:

1. Drill-down bảng danh sách học viên theo filter hiện tại.
2. Export CSV/Excel cho dữ liệu đã lọc.
3. Cron/Task Scheduler cho sync định kỳ.
4. Tách UI thành tab:
   - Overview
   - Health Movement
   - Care Effectiveness
   - Renewal Correlation
   - Revenue Forecast
5. Thêm regression tests cho:
   - DB rỗng.
   - Sync thất bại.
   - Sheet đổi tên cột.
   - Filter không có kết quả.

---

## 16. Tài liệu liên quan

- Đặc tả nghiệp vụ:

```text
CSS/SPW.md
```

- Trạng thái triển khai chi tiết:

```text
CRM-Dashboard/CRM-Dashboard/template/REQUESTS.md
template/REQUESTS.md
```

- Phase tracker:

```text
CRM-Dashboard/docs/runbooks/phase_status.md
```
