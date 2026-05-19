# Phase 0: Xây dựng Dashboard cơ bản

## Mục tiêu
Xây dựng trang Dashboard Admin với toàn bộ các chỉ số, thống kê từ Zeus LMS database.

## Tham khảo
- Metrics specification: `/Users/que/Downloads/zeus/Dashboard.md`
- Database schema: `/Users/que/Downloads/zeus/zeus_core.sql`
- API reference: `/Users/que/Downloads/zeus/zeus_core_api-dev`

## Yêu cầu kỹ thuật
- Framework: Laravel 10
- Docker services: MySQL 8.0, Nginx, PHP 8.2-FPM, Redis
- Frontend: Tailwind CSS, Chart.js, Alpine.js

## Tasks hoàn thành

### Task 0.1: Docker Setup ✅
- [x] Tạo docker-compose.yml với 4 services
- [x] Cấu hình Nginx reverse proxy
- [x] Dockerfile cho PHP-FPM với extensions cần thiết

### Task 0.2: Laravel Application Structure ✅
- [x] Tạo cấu trúc thư mục Laravel
- [x] Config database với dual connection (local + Zeus Core)
- [x] Config cache với Redis

### Task 0.3: Models ✅
- [x] User model với scopes (teachers, learners, parents, affiliates)
- [x] Order model với constants cho types và status
- [x] OrderLesson model với lesson status tracking
- [x] GroupClass, TeacherStat, ReportedIssue, RatingReview, Transaction models

### Task 0.4: Dashboard Service ✅
- [x] DashboardService với các method thống kê
- [x] Cache layer cho performance
- [x] Chart data generation (30 days)

### Task 0.5: Controllers & Routes ✅
- [x] DashboardController với 5 views
- [x] API endpoints cho charts
- [x] Web routes cho navigation

### Task 0.6: Views ✅
- [x] Layout chính với sidebar navigation
- [x] Trang Tổng quan (Overview)
- [x] Trang Vận hành hôm nay (Daily Ops)
- [x] Trang Giáo viên (Teachers)
- [x] Trang Doanh thu (Revenue)
- [x] Trang Chất lượng (Quality)

### Task 0.7: Database Import
- [x] Script import zeus_core.sql vào MySQL container

## Nạp Database Zeus Core

```bash
# Copy file SQL vào container
docker cp /Users/que/Downloads/zeus/zeus_core.sql zeus-dashboard-mysql:/tmp/

# Import vào database
docker exec -it zeus-dashboard-mysql mysql -uroot -psecret zeus_dashboard < /tmp/zeus_core.sql
```

## Chỉ số Dashboard bao gồm

### 1. Tổng quan (Overview)
- Tổng số Giáo viên, Học sinh, Phụ huynh, Affiliate
- User mới đăng ký (hôm nay/tuần/tháng)
- Tỷ lệ xác thực
- Doanh thu hôm nay/tuần/tháng
- Ca học hôm nay và tỷ lệ hoàn thành

### 2. Vận hành Hôm nay
- Lessons: total, completed, scheduled, unscheduled, cancelled
- Group Classes: scheduled, completed
- Reported Issues: in_progress, resolved, escalated, closed

### 3. Giáo viên
- Tổng bài đã dạy, lớp nhóm đã dạy
- Số học sinh unique
- Đánh giá trung bình và số reviews
- Thanh toán: đã trả, chờ thanh toán, hoa hồng

### 4. Doanh thu
- Doanh thu theo thời gian
- Đơn hàng theo loại (Lesson, Subscription, Group Class, etc.)
- Trạng thái đơn hàng
- Phân bố thanh toán

### 5. Chất lượng
- Tỷ lệ hoàn thành/hủy bài học
- Thời lượng trung bình
- Phân loại Trial vs Regular
- Rating reviews statistics

## Tiếp theo: Phase 1
- Documentation & Deployment Guide