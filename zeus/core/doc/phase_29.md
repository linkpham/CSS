# Phase 29 - Fix Export 504 Timeout & Login Status Check ✅

## Yêu cầu ban đầu

1. **Export báo cáo bị lỗi 504 Gateway Timeout**:
   ```
   Failed to load resource: the server responded with a status of 504 (Gateway Time-out)
   revenue?program=speakwell:2303  Export error: SyntaxError: Unexpected token '<', "<html>
   <h"... is not valid JSON
   ```

2. **Lỗi logic "HV chưa đăng nhập"**:
   - Học viên `thuongnt250693@gmail.com` đã học nhưng vẫn nằm trong danh sách "chưa từng login trong tuần"
   - Cần kiểm tra thêm bảng `tbl_user_auth_token` để xác định chính xác HV đã login hay chưa

## Giải pháp đã triển khai

### 1. Fix Export 504 Gateway Timeout

**Vấn đề**: Khi `QUEUE_CONNECTION=sync` hoặc queue worker không chạy, job xử lý export chạy đồng bộ và gây timeout.

**Giải pháp**: Thêm kiểm tra queue driver và xử lý fallback:

- **Async mode** (redis/database): Dispatch job như bình thường
- **Sync mode**: Xử lý trực tiếp trong request với error handling

**Files thay đổi**:
- `app/Http/Controllers/DashboardController.php`:
  - Thêm kiểm tra `config('queue.default')` 
  - Nếu driver là `redis`, `database`, `beanstalkd`, hoặc `sqs`: dispatch job async
  - Nếu driver là `sync`: xử lý trực tiếp với try/catch và cập nhật cache status

### 2. Sửa logic "HV chưa đăng nhập"

**Vấn đề**: Logic cũ chỉ kiểm tra `user_lastseen IS NULL`, nhưng không đầy đủ vì:
- Một số user có thể đã login nhưng `user_lastseen` chưa được cập nhật
- Bảng `tbl_user_auth_token` chứa thông tin token đăng nhập chính xác hơn

**Giải pháp**: Thêm điều kiện kiểm tra `tbl_user_auth_token`:
```sql
-- Logic mới
SELECT COUNT(*) FROM tbl_users 
WHERE user_id IN (
    SELECT DISTINCT order_user_id 
    FROM tbl_order_lessons ol 
    JOIN tbl_orders o ON ol.ordles_order_id = o.order_id 
    WHERE DATE(ol.ordles_lesson_starttime) = CURDATE() 
    AND ol.ordles_tlang_id IN (533,558,560,...)
) 
AND user_lastseen IS NULL 
AND user_deleted IS NULL
AND user_id NOT IN (SELECT usrtok_user_id FROM tbl_user_auth_token)
```

**Files thay đổi**:
- `app/Services/DashboardService.php`:
  - `getNeverLoggedInStudentsWithLessons()`: Thêm query `tbl_user_auth_token` và điều kiện `whereNotIn`
  - `getNeverLoggedInStudentsDetail()`: Tương tự, thêm kiểm tra auth token

- `resources/views/dashboard/daily-ops.blade.php`: Cập nhật SQL tooltip
- `resources/views/dashboard/index.blade.php`: Cập nhật tất cả SQL tooltips

## Testing

1. **Export**: 
   - Nếu queue driver = `sync`: Export xử lý trực tiếp, không còn 504
   - Nếu queue driver = `redis`: Export chạy async như bình thường

2. **Login Status**:
   - Học viên có entry trong `tbl_user_auth_token` sẽ không còn xuất hiện trong danh sách "chưa đăng nhập"
   - VD: `SELECT * FROM tbl_user_auth_token WHERE usrtok_user_id = 4950` - nếu có kết quả, user này đã login

## Commit

```
fix(dashboard): resolve export 504 timeout and login status accuracy (Phase 29)

- Add queue driver check in export API to handle sync queue fallback
- Process export directly when queue driver is sync instead of dispatching job
- Add tbl_user_auth_token check to never-logged-in students logic
- Exclude users with auth tokens from "chưa đăng nhập" lists
- Update SQL tooltips in blade templates to reflect new query logic

Fixes: 504 Gateway Timeout on export, false positives in login status

Refs: doc/phase_29.md
```
