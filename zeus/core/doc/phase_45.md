# Phase 45 - Fix Missing QUEUE_FAILED_DRIVER in Server Deployment

## Vấn đề

Vẫn tiếp tục lỗi khi triển khai trên server:
```
❌ Lỗi: App\Jobs\ProcessUsageReportExport has been attempted too many times.
```

## Nguyên nhân gốc rễ

Phase 44 đã sửa `config/queue.php` để dùng `QUEUE_FAILED_DRIVER=null` làm default, nhưng **DEPLOY-SERVER.sh script thiếu biến `QUEUE_FAILED_DRIVER=null`** khi tạo file `.env` trên server.

Điều này có nghĩa là:
1. Locally: `.env.example` có `QUEUE_FAILED_DRIVER=null` → hoạt động tốt
2. Server: `.env` được tạo bởi `DEPLOY-SERVER.sh` **không có** `QUEUE_FAILED_DRIVER` → dùng default từ config, nhưng config cache có thể không được refresh đúng cách

Khi job fail, Laravel cố gắng ghi vào bảng `failed_jobs` (trên Zeus Core readonly database) → gây lỗi.

## Giải pháp

Thêm `QUEUE_FAILED_DRIVER=null` vào phần tạo `.env` trong `DEPLOY-SERVER.sh`:

```bash
QUEUE_CONNECTION=redis
QUEUE_FAILED_DRIVER=null    # ← Thêm dòng này
SESSION_DRIVER=file
```

## Files Changed

1. `DEPLOY-SERVER.sh` - Thêm `QUEUE_FAILED_DRIVER=null` vào production .env template

## Deployment

```bash
# Re-deploy để cập nhật .env trên server
./DEPLOY-SERVER.sh

# Hoặc chỉ cần thêm thủ công vào server .env:
ssh -i ~/Downloads/zeus/quenn quenn@13.215.57.82 "echo 'QUEUE_FAILED_DRIVER=null' >> /var/www/zeus-dashboard/src/.env"

# Sau đó clear config cache:
ssh -i ~/Downloads/zeus/quenn quenn@13.215.57.82 "cd /var/www/zeus-dashboard && sudo docker exec zeus-dashboard-app php artisan config:clear && sudo docker exec zeus-dashboard-app php artisan config:cache"
```

## Kết quả mong đợi

- ✅ Failed jobs không còn cố gắng ghi vào database
- ✅ Export job chạy bình thường trên server
- ✅ Lỗi "attempted too many times" được khắc phục hoàn toàn 
