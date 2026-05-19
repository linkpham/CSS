# Phase 49 - Fix PHP-FPM Slowlog Permission Denied

## Vấn đề
DEPLOY_LOCAL.sh gặp lỗi container restarting:
```
Error response from daemon: Container ... is restarting, wait until the container is running
```

## Nguyên nhân
Container `zeus-dashboard-app` liên tục restart do lỗi PHP-FPM:
```
ERROR: Unable to create or open slowlog(/var/log/php-fpm-slow.log): Permission denied (13)
ERROR: failed to post process the configuration
ERROR: FPM initialization failed
```

Trong Phase 48, file `docker/php/www.conf` được thêm vào với cấu hình slowlog:
```ini
slowlog = /var/log/php-fpm-slow.log
```

Vấn đề: Dockerfile chạy PHP-FPM với user `www` (không phải root), nên không có quyền ghi vào `/var/log/`.

## Giải pháp
Thay đổi đường dẫn slowlog sang thư mục mà user `www` có quyền ghi:

```ini
# Trước (Phase 48)
slowlog = /var/log/php-fpm-slow.log

# Sau (Phase 49)
slowlog = /var/www/storage/logs/php-fpm-slow.log
```

## File thay đổi
- `docker/php/www.conf`: Cập nhật đường dẫn slowlog

## Kiểm tra
```bash
./DEPLOY-LOCAL.sh
docker-compose ps
# Tất cả containers phải có STATUS "Up"
```

## Kết quả
✅ Tất cả containers khởi động thành công
✅ Dashboard hoạt động tại http://localhost:8080
✅ Cache refresh hoàn tất
