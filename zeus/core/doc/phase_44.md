# Phase 44 - Fix Queue Failed Jobs Driver for Readonly Database

## Vấn đề

Khi chạy export trên server với `DEPLOY-SERVER.sh`, gặp lỗi:

```
❌ Lỗi: App\Jobs\ProcessUsageReportExport has been attempted too many times.
```

**Chi tiết từ log**:
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'zeus_core.export_jobs' doesn't exist
...
ProcessUsageReportExport.php(464): Illuminate\Database\Eloquent\Builder->first()
...
ProcessUsageReportExport->failed(Object(Illuminate\Database\QueryException))
```

## Nguyên nhân gốc rễ

Có **2 vấn đề kết hợp**:

### 1. Code cũ chưa được deploy

Server vẫn chạy phiên bản cũ của `ProcessUsageReportExport.php` (có 464+ dòng) với code:
```php
ExportJob::where('export_id', $this->exportId)->first(); // Line 464
```

Trong khi phiên bản Phase 43 đã xóa code này (chỉ còn 442 dòng).

### 2. Failed Jobs Driver dùng database

Khi job fail, Laravel cố gắng lưu vào bảng `failed_jobs`:
```php
// config/queue.php
'failed' => [
    'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),  // ❌ Dùng database
    'table' => 'failed_jobs',  // Bảng không tồn tại!
]
```

Do database là **readonly**, không có bảng `failed_jobs` → Lỗi "too many times" xuất hiện.

## Giải pháp

### 1. Thay đổi QUEUE_FAILED_DRIVER thành 'null'

**File**: `config/queue.php`

```php
'failed' => [
    'driver' => env('QUEUE_FAILED_DRIVER', 'null'),  // ✅ Không dùng database
    'database' => env('DB_CONNECTION', 'mysql'),
    'table' => 'failed_jobs',
],
```

**Giải thích**: Driver `null` sẽ log lỗi nhưng không cố gắng lưu vào database.

### 2. Cập nhật .env.example

Thêm biến môi trường:
```env
QUEUE_FAILED_DRIVER=null
```

### 3. Re-deploy để cập nhật code Phase 43

Đảm bảo server chạy phiên bản mới nhất của `ProcessUsageReportExport.php` (đã xóa code ExportJob).

## Files Changed

1. `config/queue.php` - Đổi default failed driver từ `database-uuids` sang `null`
2. `.env.example` - Thêm `QUEUE_FAILED_DRIVER=null`

## Trade-offs

| Aspect | Trước | Sau |
|--------|-------|-----|
| Failed job tracking | Lưu vào database | Chỉ log (không persistent) |
| Retry failed jobs | Có thể từ `failed_jobs` table | Phải dispatch lại thủ công |
| Server compatibility | ❌ Lỗi với readonly DB | ✅ Hoạt động bình thường |

## Deployment

```bash
# Cập nhật code và restart
./DEPLOY-SERVER.sh upgrade

# Hoặc nếu cần clear config cache
docker exec -it zeus-dashboard-app php artisan config:clear
docker exec -it zeus-dashboard-app php artisan config:cache
```

## Kết quả mong đợi

- ✅ Export job không còn lỗi "attempted too many times"
- ✅ Failed jobs được log thay vì crash
- ✅ Export hoạt động bình thường trên server readonly database
