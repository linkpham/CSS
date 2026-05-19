# Phase 56 - Fix Queue Container Crash Loop

## Vấn đề
Queue container liên tục restart với 2 lỗi:
1. **Permission denied**: `/var/www/storage/logs/laravel.log` không ghi được
2. **CollisionServiceProvider not found**: Dev package bị cache nhưng không được cài trên production

## Nguyên nhân gốc
Queue container khởi động **trước khi** app container hoàn tất:
- Composer install (--no-dev) chưa chạy
- Storage permissions chưa được set
- Config cache chứa dev packages chưa được clear

## Giải pháp
Tạo `queue-entrypoint.sh` script:
1. Chờ `vendor/autoload.php` tồn tại (chứng tỏ composer đã install)
2. Tạo storage directories nếu chưa có
3. Xóa stale config cache nếu chứa CollisionServiceProvider
4. Chạy queue worker

## Files đã thay đổi
- `docker/php/queue-entrypoint.sh` - Entrypoint script mới
- `docker-compose.yml` - Dùng entrypoint script, chạy với user root
- `docker-compose.prod.yml` - Tương tự
