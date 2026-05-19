# Phase 28 - Background Job Export with Progress ✅

## Yêu cầu
- Có lỗi xảy ra sau khi thực hiện phase 27:
```
Failed to load resource: the server responded with a status of 504 (Gateway Time-out)
revenue?program=speakwell:2256  Export error: Error: Export failed
    at revenue?program=speakwell:2242:45
```
- Cần chạy background job cho việc export và báo cáo tiến trình về cho frontend thay vì chờ đợi quá lâu. Cần phải cho phép người dùng rời khỏi trình duyệt mà chế độ export vẫn hoạt động bình thường.

## Giải pháp đã triển khai

### 1. Tạo Background Job (`ProcessUsageReportExport.php`)
- Job xử lý export báo cáo sử dụng gói học trong background
- Cập nhật tiến trình (progress) vào Redis cache theo thời gian thực
- Lưu file CSV vào `storage/app/exports/`
- Xử lý timeout lên đến 1 giờ

### 2. Tracking Progress qua Redis Cache
- Sử dụng cache key: `export_job:{exportId}`
- Cập nhật realtime: status, progress (0-100%), message
- Lưu trữ 24h sau khi hoàn tất

### 3. API Endpoints mới (Phase 28)
```
POST /api/start-export-usage-report
    - Khởi tạo export job và trả về export_id

GET /api/export-status/{exportId}
    - Lấy trạng thái và tiến trình export

GET /api/download-export/{exportId}
    - Tải file CSV sau khi hoàn tất

DELETE /api/cancel-export/{exportId}
    - Hủy và dọn dẹp export
```

### 4. Cập nhật Frontend (`revenue.blade.php`)
- Thêm Progress Bar với hiệu ứng gradient
- Polling status mỗi 1.5 giây
- Hiển thị:
  - Tiến trình %
  - Thông báo trạng thái
  - Nút "Tải file" khi hoàn tất
  - Nút "Đóng" để hủy/đóng
- Hỗ trợ người dùng rời khỏi trang và quay lại sau

### 5. Cấu hình Queue
- Thêm `config/queue.php` với hỗ trợ Redis và Database driver
- Cập nhật `.env.example` để sử dụng `QUEUE_CONNECTION=redis`
- Yêu cầu chạy queue worker: `php artisan queue:work`

## Files đã thay đổi/tạo mới
- `app/Jobs/ProcessUsageReportExport.php` (NEW)
- `app/Http/Controllers/DashboardController.php` (MODIFIED)
- `routes/web.php` (MODIFIED)
- `resources/views/dashboard/revenue.blade.php` (MODIFIED)
- `config/queue.php` (NEW)
- `.env.example` (MODIFIED)

## Hướng dẫn sử dụng

### Chạy Queue Worker (cần thiết để background job hoạt động)
```bash
# Trong docker container
docker exec -it zeus-dashboard-app php artisan queue:work redis --queue=default

# Hoặc sử dụng supervisor (recommended cho production)
```

### Cấu hình .env cho production
```
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PORT=6379
```

## Commit
```
feat(revenue): implement background export with progress tracking (Phase 28)

- Add ProcessUsageReportExport job for background processing
- Track export progress in Redis cache with real-time updates
- Add APIs: start-export, export-status, download-export, cancel-export
- Update frontend with progress bar and polling
- Prevent 504 Gateway Timeout by offloading to queue worker
- Users can leave browser while export continues

Refs: doc/phase_28.md
```
