# Zeus Dashboard - Hướng dẫn Phát triển

## Kiểm tra tiến độ

```bash
git status              # Xem branch hiện tại và thay đổi chưa commit
git diff                # Xem những gì đã chỉnh sửa
git log --oneline -5    # Commit gần đây để hiểu context
```

## Các Phase phát triển

- **Phase 0** - Xây dựng Dashboard cơ bản với Docker → [doc/phase_0.md](doc/phase_0.md) ✅
  - Tạo Laravel project với Docker (mysql, nginx, php)
  - Dashboard tổng quan với các chỉ số từ Zeus Core database

- **Phase 1** - Mở rộng tính năng → [doc/phase_1.md](doc/phase_1.md) ✅
  - Authentication & Authorization
  - Real-time updates với WebSocket

- **Phase 2** - Mở rộng Dashboard → [doc/phase_2.md](doc/phase_2.md) ✅
  - Search & Filter functionality
  - Additional metrics (Conversion funnel, Wallet stats, Top performers)
  - Enhanced charts and data tables

- **Phase 3** - Lộ trình Học tập & Nhật ký GV → [doc/phase_3.md](doc/phase_3.md) ✅
  - Learning Path module (Curriculum, Program tracking)
  - Teacher Feedback quality metrics
  - Charts: Session distribution, Feedback submission trend

- **Phase 4** - Quản trị Giáo viên & Bài kiểm tra → [doc/phase_4.md](doc/phase_4.md) ✅
  - Teacher Leave Management (nghỉ phép, vi phạm, quota)
  - Quiz/Exam statistics (tỷ lệ pass/fail, điểm trung bình)
  - Charts: Leave trend, Pass/Fail trend

- **Phase 5** - Tiếp tục thực hiện yêu cầu mới → [doc/phase_5.md](doc/phase_5.md) ✅
- **Phase 6** - Session Stats Reorganization & KPI Enhancement → [doc/phase_6.md](doc/phase_6.md) ✅
- **Phase 7** - Session Stats Hierarchical Reorganization → [doc/phase_7.md](doc/phase_7.md) ✅
- **Phase 8** - Dashboard UI Adjustments → [doc/phase_8.md](doc/phase_8.md) ✅
- **Phase 9** - SQL Tooltips & UI Improvements → [doc/phase_9.md](doc/phase_9.md) ✅
- **Phase 10** - Fix SQL Tooltips Consistency → [doc/phase_10.md](doc/phase_10.md) ✅
- **Phase 11** - URGENT FIX: Total Completed Sessions Count → [doc/phase_11.md](doc/phase_11.md) ✅
- **Phase 12** - ClassIn Data Breakdown with 30-min Threshold → [doc/phase_12.md](doc/phase_12.md) ✅
- **Phase 13** - Account Menu & Horizontal Scroll Fix → [doc/phase_13.md](doc/phase_13.md) ✅
- **Phase 14** - Fix Cannot Redeclare getAcceptanceCodeColors → [doc/phase_14.md](doc/phase_14.md) ✅
- **Phase 15** - Fix Null User Property Access Error → [doc/phase_15.md](doc/phase_15.md) ✅
- **Phase 16** - User Profile to Sidebar Bottom → [doc/phase_16.md](doc/phase_16.md) ✅
- **Phase 17** - Fix User Profile Display & SQL/Revenue Access Control → [doc/phase_17.md](doc/phase_17.md) ✅
- **Phase 18** - Improve Cache Refresh TUI with Visual Progress Bar → [doc/phase_18.md](doc/phase_18.md) ✅
- **Phase 19** - Permission-based Dashboard Access Control → [doc/phase_19.md](doc/phase_19.md) ✅
...
- **Phase N** - Tiếp tục thực hiện yêu cầu mới → [doc/phase_N.md](doc/phase_N.md)
(với N là file có số thứ tự cuối cùng trong thư mục `doc`)


## Quy trình làm việc

1. Sau khi hoàn thành các bước, thêm status note trong commit message
2. Commit theo chuẩn Angular/Commitizen
3. Sau khi commit xong tạo ra phase kế tiếp `doc/phase_<số thứ tự kế tiếp>.md`
### Ví dụ commit message:

```bash
git commit -m "feat(dashboard): implement overview statistics

- Add DashboardService with user/order/lesson stats
- Create blade templates with Tailwind CSS
- Implement Chart.js for revenue and registration charts
- Phase 0 complete, next: Phase 1 (authentication)

Refs: doc/phase_0.md"
```

## Cấu trúc Project

```
zeus-dashboard/
├── doc/                    # Tài liệu các phase
│   └── phase_0.md         # Phase 0: Dashboard cơ bản
├── docker/                 # Docker configuration
│   ├── nginx/             # Nginx config
│   ├── php/               # PHP-FPM Dockerfile
│   └── mysql/             # MySQL init scripts
├── src/                    # Laravel application
│   ├── app/               # Application code
│   ├── config/            # Configuration
│   ├── resources/views/   # Blade templates
│   └── routes/            # Routes
├── docker-compose.yml      # Docker services
└── HUONGDAN.md            # File này
```

## Khởi chạy Development

```bash
# Build và khởi động containers
docker-compose up -d --build

# Cài đặt dependencies
docker exec -it zeus-dashboard-app composer install
docker exec -it zeus-dashboard-app php artisan key:generate

# Truy cập: http://localhost:8080
```
## Đăng nhập
Không cần đăng nhập để xem Dashboard.

## Tham khảo

- Dashboard metrics: `/Users/que/Downloads/zeus/Dashboard.md`
- Zeus Core API: `/Users/que/Downloads/zeus/zeus_core_api-dev`
- Database schema: `/Users/que/Downloads/zeus/zeus_core.sql``