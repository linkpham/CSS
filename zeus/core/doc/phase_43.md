# Phase 43 - Fix Export Feature for Readonly Database

## Vấn đề

Khi triển khai trên server với `DEPLOY-SERVER.sh`, gặp lỗi:

```
❌ Lỗi: SQLSTATE[42S02]: Base table or view not found: 1146 Table 'zeus_core.export_jobs' doesn't exist
(Connection: mysql, SQL: select * from `export_jobs` where `export_id` = exp_xxx limit 1)
```

**Nguyên nhân gốc rễ**:
- Phase 41 đã thêm `export_jobs` table để tracking export jobs trong database
- Tuy nhiên, CSDL là **readonly** nên không thể tạo table mới

## Giải pháp

### Loại bỏ hoàn toàn dependency vào database cho export tracking

Thay vì sử dụng database (`ExportJob` model), chỉ sử dụng:
1. **Redis cache** - cho real-time tracking (đã có sẵn)
2. **File-based fallback** - kiểm tra file export tồn tại khi cache hết hạn

### 1. Cập nhật DashboardController

**File**: `app/Http/Controllers/DashboardController.php`

| Method | Thay đổi |
|--------|----------|
| `apiStartExportUsageReport()` | Xóa code tạo ExportJob record |
| `apiExportStatus()` | Thay database fallback bằng file-based check |
| `apiDownloadExport()` | Thay database fallback bằng file-based check |
| `apiPendingExports()` | Trả về empty array (không dùng database) |

### 2. Cập nhật ProcessUsageReportExport Job

**File**: `app/Jobs/ProcessUsageReportExport.php`

| Thay đổi | Chi tiết |
|----------|----------|
| Xóa `use App\Models\ExportJob` | Không còn import model |
| Xóa tham số `$exportJob` | Từ method `updateProgress()` |
| Xóa database operations | Trong `handle()` và `failed()` |

### 3. ExportJob Model vẫn giữ lại

Model `ExportJob` và migration vẫn được giữ lại trong codebase nhưng không được sử dụng.
Có thể xóa sau nếu cần cleanup.

## Kết quả

| Trước Phase 43 | Sau Phase 43 |
|----------------|--------------|
| Dùng database table `export_jobs` | Chỉ dùng Redis cache |
| Lỗi 1146 table not found | Hoạt động bình thường |
| Database fallback khi cache hết | File-based fallback |
| Persistent tracking qua database | Cache + file-based tracking |

## Trade-offs

| Aspect | Trước | Sau |
|--------|-------|-----|
| Persistence | Vĩnh viễn trong DB | 24 giờ trong Redis |
| Resume pending exports | ✅ Có thể | ❌ Không (phải chạy lại) |
| Download completed file | ✅ Luôn hoạt động | ✅ Hoạt động (file-based) |
| Performance | Chậm hơn (DB query) | Nhanh hơn (cache only) |

## Files Changed

1. `app/Http/Controllers/DashboardController.php` - Xóa database dependencies
2. `app/Jobs/ProcessUsageReportExport.php` - Xóa database dependencies

## Deployment

Không cần migration hay thay đổi database. Chỉ cần:

```bash
# Cập nhật code và restart
./DEPLOY-SERVER.sh upgrade
```
