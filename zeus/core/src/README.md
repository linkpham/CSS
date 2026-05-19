# Zeus LMS Dashboard

Hệ thống Dashboard quản trị cho Zeus Learning Management System.

## Yêu cầu

- Docker & Docker Compose
- Git

## Cài đặt

### 1. Clone repository

```bash
git clone <repository-url>
cd zeus-dashboard
```

### 2. Cấu hình môi trường

```bash
cp src/.env.example src/.env
```

Chỉnh sửa file `src/.env` để cấu hình:
- Database connection tới Zeus Core
- Redis cache settings

### 3. Khởi động Docker

```bash
docker-compose up -d --build
```

### 4. Cài đặt dependencies

```bash
docker exec -it zeus-dashboard-app composer install
docker exec -it zeus-dashboard-app php artisan key:generate
```

### 5. Truy cập Dashboard

Mở trình duyệt tại: http://localhost:8080

## Cấu trúc

```
zeus-dashboard/
├── docker/
│   ├── nginx/conf.d/app.conf     # Nginx config
│   ├── php/
│   │   ├── Dockerfile            # PHP-FPM image
│   │   └── local.ini             # PHP settings
│   └── mysql/init/               # MySQL init scripts
├── src/                           # Laravel application
│   ├── app/
│   │   ├── Http/Controllers/     # Controllers
│   │   ├── Models/               # Eloquent models
│   │   ├── Services/             # Business logic
│   │   └── Providers/            # Service providers
│   ├── config/                   # Configuration files
│   ├── resources/views/          # Blade templates
│   ├── routes/                   # Route definitions
│   └── ...
├── docker-compose.yml
└── README.md
```

## Dashboard Features

### 1. Tổng quan (Overview)
- Thống kê người dùng (Giáo viên, Học sinh, Phụ huynh, Affiliate)
- Doanh thu hôm nay/tuần/tháng
- Ca học hôm nay
- Biểu đồ doanh thu 30 ngày
- Biểu đồ đăng ký mới 30 ngày

### 2. Vận hành Hôm nay (Daily Operations)
- Chi tiết ca học hôm nay
- Lớp nhóm (Group Classes)
- Báo cáo vấn đề

### 3. Giáo viên (Teachers)
- Thống kê bài đã dạy
- Thanh toán giáo viên
- Đánh giá & phản hồi

### 4. Doanh thu (Revenue)
- Tổng quan doanh thu
- Đơn hàng theo loại
- Phân bố thanh toán

### 5. Chất lượng (Quality)
- Tỷ lệ hoàn thành bài học
- Chi tiết đánh giá
- Vấn đề báo cáo

## API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/overview` | GET | Thống kê tổng quan |
| `/api/revenue-chart` | GET | Dữ liệu biểu đồ doanh thu |
| `/api/user-chart` | GET | Dữ liệu biểu đồ đăng ký |

## Docker Services

| Service | Port | Description |
|---------|------|-------------|
| webserver (nginx) | 8080 | Web server |
| app (php-fpm) | 9000 | PHP application |
| db (mysql) | 3307 | Local MySQL database |
| redis | 6380 | Caching |

## Kết nối Zeus Core Database

Dashboard kết nối tới Zeus Core database (read-only) để lấy dữ liệu thống kê. Cấu hình trong `.env`:

```env
ZEUS_DB_HOST=zeus-aurora-cluster-prod.cluster-xxx.rds.amazonaws.com
ZEUS_DB_PORT=3306
ZEUS_DB_DATABASE=zeus_core
ZEUS_DB_USERNAME=readonly_user
ZEUS_DB_PASSWORD=readonly_password
```

## Development

### Xem logs

```bash
docker-compose logs -f app
docker-compose logs -f webserver
```

### Truy cập container

```bash
docker exec -it zeus-dashboard-app bash
```

### Clear cache

```bash
docker exec -it zeus-dashboard-app php artisan cache:clear
docker exec -it zeus-dashboard-app php artisan view:clear
```

## License

MIT License
