# Phase 88

## Yêu cầu
Sửa lỗi CareSoft sử dụng SQLite không tồn tại, chuyển sang dùng MySQL:
```
Database file at path [/var/www/storage/app/caresoft.sqlite] does not exist.
```
- Yêu cầu dùng MySQL database thay vì SQLite cho CareSoft cache tables
- API token cần đưa vào DEPLOY_LOCAL.sh

## Giải pháp

### 1. Thêm MySQL container cho Dashboard local data
- Tạo MySQL container `zeus-dashboard-mysql` trong docker-compose.yml và docker-compose.prod.yml
- Database: `zeus_dashboard` cho CareSoft cache tables (không ảnh hưởng zeus_core read-only)

### 2. Cập nhật database configuration
- `src/config/database.php`: Chuyển caresoft connection từ SQLite sang MySQL
- Sử dụng environment variables: `DASHBOARD_DB_HOST`, `DASHBOARD_DB_DATABASE`, etc.

### 3. Tạo migration cho CareSoft tables
- `database/migrations/2025_02_10_000001_create_caresoft_tables.php`
- Tables: cs_agents, cs_groups, cs_services, cs_tickets, cs_calls, cs_chats, cs_sync_logs

### 4. Cập nhật CareSoftSync command
- Thay đổi từ SQLite syntax sang Laravel Schema Builder (MySQL compatible)
- `app/Console/Commands/CareSoftSync.php`

### 5. Cập nhật Deploy scripts
- **DEPLOY-LOCAL.sh**: Thêm CARESOFT_DOMAIN, CARESOFT_API_TOKEN, DASHBOARD_DB_* variables
- **DEPLOY-SERVER.sh**: Tương tự với production defaults

## Files đã thay đổi

1. `docker-compose.yml` - Thêm MySQL container và volume
2. `docker-compose.prod.yml` - Thêm MySQL container và volume
3. `src/config/database.php` - Cập nhật caresoft connection sang MySQL
4. `src/database/migrations/2025_02_10_000001_create_caresoft_tables.php` - Mới
5. `src/app/Console/Commands/CareSoftSync.php` - Cập nhật table creation
6. `DEPLOY-LOCAL.sh` - Thêm CareSoft và Dashboard DB config
7. `DEPLOY-SERVER.sh` - Thêm CareSoft và Dashboard DB config

## Cấu hình mới

### Environment Variables
```bash
# CareSoft API Configuration
CARESOFT_DOMAIN=<your-caresoft-domain>
CARESOFT_API_TOKEN=<your-api-token>

# Dashboard local MySQL (for CareSoft cache tables)
DASHBOARD_DB_HOST=mysql
DASHBOARD_DB_PORT=3306
DASHBOARD_DB_DATABASE=zeus_dashboard
DASHBOARD_DB_USERNAME=dashboard_user
DASHBOARD_DB_PASSWORD=dashboard_password
```

### Docker Services
```yaml
mysql:
  image: mysql:8.0
  container_name: zeus-dashboard-mysql
  environment:
    MYSQL_DATABASE: zeus_dashboard
    MYSQL_USER: dashboard_user
    MYSQL_PASSWORD: dashboard_password
    MYSQL_ROOT_PASSWORD: root_password
  volumes:
    - zeus-dashboard-mysql:/var/lib/mysql
```

## Hướng dẫn Deploy

### Local Development
```bash
./DEPLOY-LOCAL.sh  # Full installation với MySQL container mới
```

### Server Production
```bash
./DEPLOY-SERVER.sh  # Full installation với MySQL container mới
```

### Sau khi deploy, chạy migration cho CareSoft tables
```bash
docker exec zeus-dashboard-app php artisan migrate --database=caresoft --path=database/migrations/2025_02_10_000001_create_caresoft_tables.php
```

### Sync CareSoft data
```bash
docker exec zeus-dashboard-app php artisan caresoft:sync --type=all
```

## Ghi chú

- CareSoft API domain cần được xác nhận lại
- API token đã được cấu hình trong DEPLOY_LOCAL.sh và DEPLOY_SERVER.sh
- CareSoft cache tables được tạo trong `zeus_dashboard` database (MySQL container local)
- Zeus Core database vẫn là read-only replica, không bị ảnh hưởng

## Status: ✅ COMPLETE
