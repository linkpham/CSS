# Phase 58 - Fix Laravel Scheduler Not Running

## Vấn đề
- Task refresh cache mỗi 15 phút và force refresh lúc 23:30 không hoạt động
- Nguyên nhân: Không có container nào chạy `php artisan schedule:run`
- Laravel scheduler cần được gọi mỗi phút để kiểm tra và thực thi các scheduled tasks

## Giải pháp
Thêm container `scheduler` riêng vào Docker để chạy Laravel scheduler mỗi phút.

## Thay đổi

### 1. Tạo file: `docker/php/scheduler-entrypoint.sh`
Script chạy vòng lặp gọi `schedule:run` mỗi 60 giây:
- Chờ vendor/autoload.php sẵn sàng
- Đảm bảo storage directories tồn tại
- Xóa stale config cache nếu cần
- Chạy `php artisan schedule:run` mỗi phút trong vòng lặp vô hạn

### 2. Cập nhật: `docker-compose.yml`
Thêm service `scheduler`:
```yaml
scheduler:
  container_name: zeus-dashboard-scheduler
  command: /bin/bash /usr/local/bin/scheduler-entrypoint.sh
  depends_on:
    - app
    - redis
```

## Cách deploy
```bash
docker-compose up -d --build scheduler
```

## Kiểm tra
```bash
# Xem logs scheduler
docker logs -f zeus-dashboard-scheduler

# Xem scheduled tasks
docker exec zeus-dashboard-app php artisan schedule:list
```
