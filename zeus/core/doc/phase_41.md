# Phase 41 - Background Export with Persistent Job Tracking

## Vấn đề

Sau Phase 40, xuất báo cáo vẫn gặp lỗi 524 (Cloudflare timeout):

```
Failed to load resource: the server responded with a status of 524 ()
Failed to parse response: SyntaxError: Unexpected token '<', "<!DOCTYPE "... is not valid JSON
Export error: Error: Lỗi server (524): Không thể xử lý phản hồi
```

**Nguyên nhân**:
- Mặc dù query đã được tối ưu (~30s), các exports với date range lớn vẫn có thể timeout
- Cloudflare timeout ở 100 giây
- Nếu người dùng đóng trình duyệt, tiến trình export bị mất (chỉ tracking qua Redis cache)

## Yêu cầu

- Chức năng "Báo cáo Sử dụng" phải hoạt động ở dạng background
- Hiển thị tiến trình export
- Người dùng có thể thoát khỏi trình duyệt bất cứ lúc nào và quay lại sau để download file

## Giải pháp

### 1. Thêm Queue Worker Container

**File**: `docker-compose.yml`

```yaml
# Queue Worker for background jobs (Phase 41)
queue:
  build:
    context: .
    dockerfile: docker/php/Dockerfile
  container_name: zeus-dashboard-queue
  restart: unless-stopped
  working_dir: /var/www
  command: php artisan queue:work redis --sleep=3 --tries=1 --timeout=600 --memory=512
  networks:
    - zeus-dashboard-network
  depends_on:
    - app
    - redis
```

### 2. Database Tracking cho Export Jobs

**Migration**: `database/migrations/2025_01_23_000001_create_export_jobs_table.php`

```php
Schema::create('export_jobs', function (Blueprint $table) {
    $table->id();
    $table->string('export_id', 64)->unique();
    $table->unsignedBigInteger('user_id')->nullable();
    $table->string('type', 50)->default('usage_report');
    $table->enum('status', ['pending', 'processing', 'completed', 'failed']);
    $table->unsignedTinyInteger('progress')->default(0);
    $table->string('message', 255)->nullable();
    $table->date('period_start')->nullable();
    $table->date('period_end')->nullable();
    $table->string('filename', 255)->nullable();
    $table->unsignedInteger('record_count')->nullable();
    $table->text('error')->nullable();
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
});
```

**Model**: `app/Models/ExportJob.php`
- Quản lý trạng thái export jobs
- Methods: `markAsProcessing()`, `markAsCompleted()`, `markAsFailed()`, `updateProgress()`
- `toApiResponse()` để format data cho frontend

### 3. Cập nhật ProcessUsageReportExport Job

- Cập nhật cả Redis cache (real-time polling) VÀ database (persistent storage)
- Fallback khi cache expire: lấy data từ database

### 4. API Endpoints mới

| Endpoint | Mô tả |
|----------|-------|
| `GET /api/pending-exports` | Lấy danh sách exports đang chạy của user |
| `GET /api/export-status/{id}` | Trạng thái export (cache + database fallback) |
| `GET /api/download-export/{id}` | Tải file (database fallback) |

### 5. Frontend Updates

**File**: `resources/views/dashboard/revenue.blade.php`

- `checkPendingExports()`: Kiểm tra exports đang chạy khi load page
- Tự động resume tracking nếu có export đang chạy
- Hiển thị progress và cho phép download khi complete

## Kết quả

| Feature | Trước Phase 41 | Sau Phase 41 |
|---------|----------------|--------------|
| Export bị timeout | ❌ 524 error | ✅ Background processing |
| Đóng browser | ❌ Mất progress | ✅ Persistent tracking |
| Resume tracking | ❌ Không có | ✅ Tự động resume |
| Queue processing | ❌ Sync mode | ✅ Redis queue + worker |

## Files Changed

1. `docker-compose.yml` - Thêm queue worker container
2. `database/migrations/2025_01_23_000001_create_export_jobs_table.php` - Migration mới
3. `app/Models/ExportJob.php` - Model mới
4. `app/Jobs/ProcessUsageReportExport.php` - Cập nhật job tracking
5. `app/Http/Controllers/DashboardController.php` - Cập nhật APIs
6. `routes/web.php` - Thêm route mới
7. `resources/views/dashboard/revenue.blade.php` - Frontend updates

## Deployment

```bash
# 1. Restart containers with new queue worker
docker-compose down
docker-compose up -d --build

# 2. Run migration
docker exec zeus-dashboard-app php artisan migrate

# 3. Clear caches
docker exec zeus-dashboard-app php artisan config:clear
docker exec zeus-dashboard-app php artisan cache:clear

# 4. Verify queue worker is running
docker logs zeus-dashboard-queue --tail 20
```

## Testing

```bash
# Check queue worker status
docker ps | grep zeus-dashboard-queue

# Test export (should complete in background)
# 1. Go to Revenue page
# 2. Select date range and click "Xuất CSV"
# 3. Close browser and reopen - progress should resume
# 4. Wait for completion and download file
```
