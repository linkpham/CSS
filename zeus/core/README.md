# Zeus Dashboard

[![Laravel](https://img.shields.io/badge/Laravel-10.x-FF2D20?logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php)](https://php.net)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker)](https://docker.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Hệ thống **Dashboard Quản trị** cho Zeus Learning Management System (LMS). Cung cấp giao diện trực quan để theo dõi và quản lý các hoạt động của hệ thống LMS.

## 📋 Mục lục

- [Tính năng](#-tính-năng)
- [Yêu cầu hệ thống](#-yêu-cầu-hệ-thống)
- [Cài đặt nhanh](#-cài-đặt-nhanh)
- [Deploy Scripts (Khuyến nghị)](#️-deploy-scripts-khuyến-nghị)
- [Hướng dẫn chi tiết](#-hướng-dẫn-chi-tiết)
  - [MacOS](#macos)
  - [Ubuntu](#ubuntu)
- [Cấu hình](#-cấu-hình)
- [Sử dụng](#-sử-dụng)
- [Cấu trúc dự án](#-cấu-trúc-dự-án)
- [API Endpoints](#-api-endpoints)
- [Xử lý sự cố](#-xử-lý-sự-cố)
- [Phát triển](#-phát-triển)
- [License](#-license)

## ✨ Tính năng

### Dashboard Modules

| Module | Mô tả |
|--------|-------|
| **Tổng quan** | Thống kê người dùng, doanh thu, biểu đồ 30 ngày |
| **Vận hành** | Ca học hôm nay, lớp nhóm, báo cáo vấn đề |
| **Giáo viên** | Thống kê bài dạy, thanh toán, đánh giá |
| **Doanh thu** | Phân tích đơn hàng, xu hướng thanh toán |
| **Chất lượng** | Tỷ lệ hoàn thành, phản hồi học viên |

### Tính năng kỹ thuật

- 🔐 **Authentication & Authorization** - Đăng nhập với phân quyền (Spatie Laravel-permission)
- 📊 **Real-time Charts** - Biểu đồ động với Chart.js
- 🚀 **Redis Caching** - Tối ưu hiệu suất truy vấn
- 🐳 **Docker Ready** - Triển khai nhanh với Docker Compose
- 📱 **Responsive Design** - Giao diện thân thiện với Tailwind CSS

## 💻 Yêu cầu hệ thống

### Bắt buộc

| Phần mềm | Phiên bản tối thiểu |
|----------|---------------------|
| Docker | 20.10+ |
| Docker Compose | 2.0+ |
| Git | 2.30+ |

### Cổng mạng

| Cổng | Dịch vụ |
|------|---------|
| 8080 | Web (Nginx) |
| 3307 | MySQL |
| 6380 | Redis |

> ⚠️ **Lưu ý:** Đảm bảo các cổng trên không bị sử dụng bởi ứng dụng khác.

## 🚀 Cài đặt nhanh

```bash
# Clone repository
git clone <repository-url>
cd zeus-dashboard

# Chạy script tự động
./scripts/setup.sh

# Truy cập: http://localhost:8080
```

## 🛠️ Deploy Scripts (Khuyến nghị)

### Local Development

Sử dụng script `DEPLOY-LOCAL.sh` để triển khai môi trường phát triển:

```bash
# Cài đặt đầy đủ (lần đầu hoặc khi có thay đổi lớn)
./DEPLOY-LOCAL.sh

# Cập nhật nhanh (chỉ khi thay đổi mã nguồn, không cần cài lại dependencies)
./DEPLOY-LOCAL.sh upgrade
```

| Lệnh | Mô tả | Thời gian |
|------|-------|-----------|
| `./DEPLOY-LOCAL.sh` | Cài đặt đầy đủ: tạo .env, build Docker, composer install | ~2-3 phút |
| `./DEPLOY-LOCAL.sh upgrade` | Cập nhật nhanh: chỉ xóa cache Laravel | ~5 giây |

### Server Deployment

Sử dụng script `DEPLOY-SERVER.sh` để triển khai lên server:

```bash
# Thiết lập biến môi trường (bắt buộc lần đầu)
export ZEUS_DB_PASSWORD='your-database-password'

# Cài đặt đầy đủ (lần đầu hoặc khi có thay đổi lớn)
./DEPLOY-SERVER.sh

# Cập nhật nhanh (chỉ khi thay đổi mã nguồn)
./DEPLOY-SERVER.sh upgrade
```

| Lệnh | Mô tả | Thời gian |
|------|-------|-----------|
| `./DEPLOY-SERVER.sh` | Cài đặt đầy đủ: rsync files, tạo .env, build Docker, composer install | ~3-5 phút |
| `./DEPLOY-SERVER.sh upgrade` | Cập nhật nhanh: rsync files, xóa và rebuild cache | ~30 giây |

### Khi nào dùng `upgrade`?

✅ **Dùng `upgrade` khi:**
- Chỉ thay đổi code PHP (Controllers, Services, Models, Views)
- Chỉ thay đổi Blade templates
- Chỉ thay đổi routes
- Chỉ sửa lỗi nhỏ

❌ **Dùng cài đặt đầy đủ khi:**
- Thêm/xóa package trong `composer.json`
- Thay đổi cấu hình Docker
- Thay đổi file `.env.example`
- Lần đầu cài đặt
- Sau khi pull code có nhiều thay đổi lớn

## 📖 Hướng dẫn chi tiết

### MacOS

#### 1. Cài đặt Docker Desktop

```bash
# Sử dụng Homebrew
brew install --cask docker

# Hoặc tải từ: https://www.docker.com/products/docker-desktop/
```

Sau khi cài đặt, khởi động Docker Desktop từ Applications.

#### 2. Clone và cấu hình

```bash
# Clone repository
git clone <repository-url>
cd zeus-dashboard

# Sao chép file môi trường
cp src/.env.example src/.env
```

#### 3. Chỉnh sửa cấu hình (nếu cần)

```bash
# Mở file .env bằng editor
nano src/.env
# Hoặc: code src/.env (VS Code)
```

#### 4. Khởi động ứng dụng

```bash
# Build và khởi động containers
docker-compose up -d --build

# Đợi containers khởi động (khoảng 30 giây)
sleep 30

# Cài đặt dependencies PHP
docker exec -it zeus-dashboard-app composer install

# Tạo application key
docker exec -it zeus-dashboard-app php artisan key:generate

# Tạo storage link
docker exec -it zeus-dashboard-app php artisan storage:link
```

#### 5. Import database (tùy chọn)

```bash
# Nếu có file zeus_core.sql
./scripts/import-db.sh
```

#### 6. Truy cập Dashboard

Mở trình duyệt: **http://localhost:8080**

---

### Ubuntu

#### 1. Cài đặt Docker

```bash
# Cập nhật packages
sudo apt-get update

# Cài đặt dependencies
sudo apt-get install -y \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg \
    lsb-release

# Thêm Docker GPG key
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# Thêm Docker repository
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu \
  $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Cài đặt Docker Engine
sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# Thêm user vào group docker (không cần sudo)
sudo usermod -aG docker $USER

# Áp dụng group mới (hoặc logout/login lại)
newgrp docker

# Kiểm tra cài đặt
docker --version
docker compose version
```

#### 2. Clone và cấu hình

```bash
# Clone repository
git clone <repository-url>
cd zeus-dashboard

# Sao chép file môi trường
cp src/.env.example src/.env

# Chỉnh sửa cấu hình nếu cần
nano src/.env
```

#### 3. Khởi động ứng dụng

```bash
# Build và khởi động containers
docker compose up -d --build

# Đợi containers khởi động
sleep 30

# Cài đặt dependencies PHP
docker exec -it zeus-dashboard-app composer install

# Tạo application key
docker exec -it zeus-dashboard-app php artisan key:generate

# Tạo storage link
docker exec -it zeus-dashboard-app php artisan storage:link
```

#### 4. Import database (tùy chọn)

```bash
# Chỉnh sửa đường dẫn SQL file trong script nếu cần
nano scripts/import-db.sh

# Chạy import
./scripts/import-db.sh
```

#### 5. Truy cập Dashboard

Mở trình duyệt: **http://localhost:8080**

hoặc **http://<server-ip>:8080** nếu cài trên server

---

## ⚙️ Cấu hình

### Biến môi trường quan trọng

Chỉnh sửa file `src/.env`:

```env
# === Application ===
APP_NAME="Zeus Dashboard"
APP_ENV=local                    # local, production
APP_DEBUG=true                   # false cho production
APP_URL=http://localhost:8080

# === Database chính (Dashboard) ===
DB_CONNECTION=mysql
DB_HOST=db                       # Tên service trong docker-compose
DB_PORT=3306
DB_DATABASE=zeus_dashboard
DB_USERNAME=zeus_user
DB_PASSWORD=secret

# === Database Zeus Core (read-only) ===
ZEUS_DB_HOST=your-zeus-core-host
ZEUS_DB_PORT=3306
ZEUS_DB_DATABASE=zeus_core
ZEUS_DB_USERNAME=readonly_user
ZEUS_DB_PASSWORD=readonly_password

# === Cache & Session ===
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=redis                 # Tên service trong docker-compose
REDIS_PORT=6379
```

### Docker Services

| Service | Container | Port nội bộ | Port host |
|---------|-----------|-------------|-----------|
| app | zeus-dashboard-app | 9000 | - |
| webserver | zeus-dashboard-nginx | 80 | 8080 |
| db | zeus-dashboard-mysql | 3306 | 3307 |
| redis | zeus-dashboard-redis | 6379 | 6380 |

### Tùy chỉnh cổng

Chỉnh sửa `docker-compose.yml` nếu cần thay đổi cổng:

```yaml
services:
  webserver:
    ports:
      - "80:80"      # Thay 8080:80 thành 80:80 cho production
```

## 📊 Sử dụng

### Các lệnh thường dùng

```bash
# Khởi động containers
docker compose up -d

# Dừng containers
docker compose down

# Xem logs
docker compose logs -f
docker compose logs -f app          # Chỉ xem logs PHP
docker compose logs -f webserver    # Chỉ xem logs Nginx

# Truy cập container PHP
docker exec -it zeus-dashboard-app bash

# Chạy Artisan commands
docker exec -it zeus-dashboard-app php artisan <command>

# Clear cache
docker exec -it zeus-dashboard-app php artisan cache:clear
docker exec -it zeus-dashboard-app php artisan config:clear
docker exec -it zeus-dashboard-app php artisan view:clear
docker exec -it zeus-dashboard-app php artisan route:clear

# Optimize cho production
docker exec -it zeus-dashboard-app php artisan optimize
```

### Quản lý Database

```bash
# Truy cập MySQL CLI
docker exec -it zeus-dashboard-mysql mysql -uroot -psecret zeus_dashboard

# Backup database
docker exec zeus-dashboard-mysql mysqldump -uroot -psecret zeus_dashboard > backup.sql

# Restore database
docker exec -i zeus-dashboard-mysql mysql -uroot -psecret zeus_dashboard < backup.sql
```

### Quản lý User & Quyền

```bash
# Tạo admin user
docker exec -it zeus-dashboard-app php artisan tinker
>>> \App\Models\Admin::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password'), 'role' => 'super_admin', 'is_active' => true]);

# Gán role (sử dụng Spatie permissions)
>>> $admin = \App\Models\Admin::find(1);
>>> $admin->assignRole('super-admin');
```

## 📁 Cấu trúc dự án

```
zeus-dashboard/
├── doc/                          # Tài liệu phát triển
│   ├── phase_0.md               # Phase 0: Dashboard cơ bản ✅
│   ├── phase_1.md               # Phase 1: Auth & Real-time
│   ├── phase_2.md               # Phase 2: Tối ưu & Deploy
│   └── phase_3.md               # Phase 3: Mở rộng
│
├── docker/                       # Docker configuration
│   ├── mysql/
│   │   └── init/                # MySQL init scripts
│   ├── nginx/
│   │   └── conf.d/
│   │       └── app.conf         # Nginx server config
│   └── php/
│       ├── Dockerfile           # PHP-FPM image
│       └── local.ini            # PHP settings
│
├── scripts/                      # Utility scripts
│   ├── setup.sh                 # One-click setup
│   └── import-db.sh             # Database import
│
├── src/                          # Laravel application
│   ├── app/
│   │   ├── Http/
│   │   │   └── Controllers/     # Dashboard controllers
│   │   ├── Models/              # Eloquent models
│   │   ├── Providers/           # Service providers
│   │   └── Services/            # Business logic
│   ├── bootstrap/               # Framework bootstrap
│   ├── config/                  # Configuration files
│   ├── database/                # Migrations & seeders
│   ├── public/                  # Public assets
│   ├── resources/
│   │   └── views/               # Blade templates
│   ├── routes/                  # Route definitions
│   ├── storage/                 # Logs, cache, uploads
│   ├── .env.example             # Environment template
│   └── composer.json            # PHP dependencies
│
├── docker-compose.yml           # Docker services
├── HUONGDAN.md                  # Hướng dẫn phát triển
└── README.md                    # File này
```

## 🔌 API Endpoints

### Dashboard Data

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/overview` | Thống kê tổng quan |
| GET | `/api/revenue-chart` | Dữ liệu biểu đồ doanh thu |
| GET | `/api/user-chart` | Dữ liệu biểu đồ đăng ký |

### Authentication

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| POST | `/login` | Đăng nhập |
| POST | `/logout` | Đăng xuất |
| GET | `/api/user` | Thông tin user hiện tại |

## 🔧 Xử lý sự cố

### Lỗi thường gặp

#### 1. Port đã được sử dụng

```
Error: Bind for 0.0.0.0:8080 failed: port is already allocated
```

**Giải pháp:**
```bash
# Tìm process đang dùng port
lsof -i :8080

# Dừng process hoặc đổi port trong docker-compose.yml
```

#### 2. Permission denied

```
Error: mkdir(): Permission denied
```

**Giải pháp:**
```bash
# Cấp quyền cho storage và cache
docker exec -it zeus-dashboard-app chmod -R 777 storage bootstrap/cache
```

#### 3. Container không khởi động

**Giải pháp:**
```bash
# Xem logs chi tiết
docker compose logs

# Rebuild containers
docker compose down -v
docker compose up -d --build
```

#### 4. MySQL connection refused

```
SQLSTATE[HY000] [2002] Connection refused
```

**Giải pháp:**
```bash
# Đợi MySQL khởi động hoàn tất
sleep 30

# Hoặc kiểm tra container
docker compose ps
docker compose logs db
```

#### 5. Composer install thất bại

**Giải pháp:**
```bash
# Xóa cache và thử lại
docker exec -it zeus-dashboard-app composer clear-cache
docker exec -it zeus-dashboard-app composer install --no-scripts
```

### Khôi phục từ đầu

```bash
# Xóa hoàn toàn và cài đặt lại
docker compose down -v --remove-orphans
docker volume prune -f
docker compose up -d --build

# Chạy setup lại
./scripts/setup.sh
```

## 👨‍💻 Phát triển

### Chạy tests

```bash
docker exec -it zeus-dashboard-app php artisan test
```

### Code style

```bash
# Kiểm tra code style
docker exec -it zeus-dashboard-app ./vendor/bin/pint --test

# Tự động fix
docker exec -it zeus-dashboard-app ./vendor/bin/pint
```

### Tạo migration

```bash
docker exec -it zeus-dashboard-app php artisan make:migration create_xxx_table
docker exec -it zeus-dashboard-app php artisan migrate
```

### Xem database

```bash
# Truy cập MySQL
docker exec -it zeus-dashboard-mysql mysql -uroot -psecret

# Trong MySQL shell
USE zeus_dashboard;
SHOW TABLES;
```

### Môi trường Production

```bash
# Tối ưu cho production
docker exec -it zeus-dashboard-app php artisan config:cache
docker exec -it zeus-dashboard-app php artisan route:cache
docker exec -it zeus-dashboard-app php artisan view:cache
docker exec -it zeus-dashboard-app php artisan optimize

# Cập nhật .env
APP_ENV=production
APP_DEBUG=false
```

## 📄 License

MIT License - Xem file [LICENSE](LICENSE) để biết thêm chi tiết.

---

## 🤝 Đóng góp

1. Fork repository
2. Tạo feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Tạo Pull Request

---

<p align="center">
  Made with ❤️ by Zeus Team
</p>
