# Phase 57 - Auto Refresh Cache Daily at 23:30

## Yêu cầu
- Hệ thống phải tự động làm mới số liệu 1 lần ("Xóa cache và tải dữ liệu mới nhất") vào lúc 23h30 hàng ngày.

## Thực hiện

### Thay đổi file: `src/app/Console/Kernel.php`

Thêm scheduled task mới để force refresh cache lúc 23:30 theo giờ Việt Nam:

```php
// Force refresh cache daily at 23:30 (Vietnam time)
// This ensures fresh data is available for the next day
$schedule->command('dashboard:refresh-cache --force')
    ->dailyAt('23:30')
    ->timezone('Asia/Ho_Chi_Minh')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/dashboard-cache-daily.log'));
```

## Ghi chú
- Sử dụng command `dashboard:refresh-cache --force` đã có sẵn
- Timezone: Asia/Ho_Chi_Minh để đảm bảo chạy đúng giờ Việt Nam
- Log riêng: `storage/logs/dashboard-cache-daily.log`
- Giữ nguyên task refresh mỗi 15 phút song song