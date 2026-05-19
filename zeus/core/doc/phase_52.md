# Phase 52 - Fix PHP-FPM Container Restart Loop

## Vấn đề
Container `zeus-dashboard-app` restart liên tục khi deploy lên server:
```
Error response from daemon: Container xxx is restarting, wait until the container is running
```

## Nguyên nhân
File `docker/php/www.conf` có cấu hình:
```ini
listen = 127.0.0.1:9000
```

Điều này chỉ cho phép PHP-FPM lắng nghe trên localhost **trong container**, nhưng nginx container cần kết nối qua Docker network (`app:9000`). Nginx không thể kết nối → PHP-FPM không nhận request → container restart do health check fail.

## Giải pháp
Thay đổi listen address để cho phép kết nối từ các container khác:
```ini
listen = 0.0.0.0:9000
```

## Files đã sửa
- `docker/php/www.conf`: Changed listen from `127.0.0.1:9000` to `0.0.0.0:9000`