# Phase 53 - Fix Container Restart Loop in DEPLOY-SERVER.sh

## Vấn đề
Khi deploy lên server, container `zeus-dashboard-app` rơi vào restart loop, khiến tất cả các lệnh `docker exec` đều fail với lỗi:
```
Error response from daemon: Container ... is restarting, wait until the container is running
```

## Nguyên nhân
1. **docker-compose.prod.yml chưa được cập nhật**: File prod vẫn dùng `--memory=512` trong khi local đã sửa thành `--memory=1024`
2. **Script không chờ container ổn định**: Chỉ `sleep 10` mà không kiểm tra container thực sự running

## Giải pháp

### 1. Cập nhật docker-compose.prod.yml
Tăng memory limit của queue worker từ 512MB lên 1024MB để xử lý export lớn:
```yaml
# Phase 50: Increased memory limit from 512 to 1024MB to handle large exports
command: php artisan queue:work redis --sleep=3 --tries=1 --timeout=600 --memory=1024
```

### 2. Thêm hàm wait_for_container trong DEPLOY-SERVER.sh
Hàm mới kiểm tra container status và chờ cho đến khi running (không còn restarting):
- Timeout 60 giây (30 attempts x 2 giây)
- Hiển thị logs nếu container exit hoặc timeout
- Dừng deploy nếu app container fail

## Files thay đổi
- `docker-compose.prod.yml` - Tăng memory limit queue worker
- `DEPLOY-SERVER.sh` - Thêm wait_for_container function

## Testing
Chạy `./DEPLOY-SERVER.sh` và verify:
1. Script chờ đúng cho đến khi container sẵn sàng
2. Hiển thị logs nếu có lỗi thay vì spam "is restarting"
3. Exit sớm nếu app container không khởi động được
