# Phase 159 - Fix: CSI Dashboard 504 Gateway Timeout

## Vấn đề
- Kể từ Phase 155, trang "Chăm sóc CSI" gặp lỗi 504 (Gateway Timeout) khi gọi `/api/csi/dashboard-data`
- Lỗi xảy ra cả khi không có bộ lọc và khi có bộ lọc ngày (date_from, date_to)
- Frontend retry 2 lần nhưng đều thất bại → trang hiển thị trắng

## Nguyên nhân gốc
- CTE `extras_one` trong `baseCte()` sử dụng `ROW_NUMBER() OVER (PARTITION BY ... ORDER BY ...)` window function kết hợp với `WHERE ... IN (SELECT ...)` correlated subquery
- Với bảng `tbl_order_lessons_extras` có dung lượng lớn, MySQL phải scan và sort toàn bộ rows matching → query time vượt quá timeout
- Tương tự với CTE `first3_extras` trong `fullCte()` (dùng cho endpoint student list)

## Giải pháp

### 1. Tối ưu `baseCte()` - thay ROW_NUMBER bằng MIN+JOIN
**Trước:**
```sql
extras_one AS (
    SELECT x.ole_ordles_id, x.ole_acceptance_code
    FROM (
        SELECT ex.ole_ordles_id, ex.ole_acceptance_code,
            ROW_NUMBER() OVER (PARTITION BY ex.ole_ordles_id ORDER BY ex.ole_id ASC) AS rn
        FROM tbl_order_lessons_extras ex
        WHERE ex.ole_ordles_id IN (SELECT ordles_id FROM lessons_base)
    ) x
    WHERE x.rn = 1
)
```

**Sau:**
```sql
extras_min AS (
    SELECT ex.ole_ordles_id, MIN(ex.ole_id) AS min_id
    FROM tbl_order_lessons_extras ex
    INNER JOIN lessons_base lb ON ex.ole_ordles_id = lb.ordles_id
    GROUP BY ex.ole_ordles_id
),
extras_one AS (
    SELECT em.ole_ordles_id, ex.ole_acceptance_code
    FROM extras_min em
    INNER JOIN tbl_order_lessons_extras ex ON ex.ole_id = em.min_id
)
```

### 2. Tối ưu `fullCte()` - cùng pattern cho first3_extras
- Thay ROW_NUMBER bằng MIN+JOIN cho `first3_extras`
- Thay `WHERE ... IN (SELECT ...)` bằng `INNER JOIN` cho `first3_all_lessons`

### 3. Thêm MySQL statement timeout
- `SET SESSION max_execution_time = 120000` (120 giây) trước khi chạy CTE nặng
- Reset lại `max_execution_time = 0` sau khi hoàn thành (cả success và error)
- Ngăn query treo vô thời hạn gây 504 từ nginx

## File thay đổi
- `src/app/Services/CsiService.php`:
  - `baseCte()`: Thay ROW_NUMBER → MIN+JOIN cho extras_one
  - `fullCte()`: Thay ROW_NUMBER → MIN+JOIN cho first3_extras; IN → INNER JOIN cho first3_all_lessons
  - `computeDashboardData()`: Thêm MySQL statement timeout 120s

## Tại sao hiệu quả
- `MIN(ole_id) GROUP BY` sử dụng index trên `ole_id` (primary key) → MySQL chỉ cần scan index, không cần sort
- `INNER JOIN` thay `IN (SELECT ...)` giúp MySQL optimizer chọn join strategy tối ưu hơn
- Statement timeout ngăn chặn query treo gây cascade failure
