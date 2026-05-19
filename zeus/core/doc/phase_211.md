# Phase 211 ✅

## Yêu cầu
- Xử lý lỗi `SQLSTATE[HY093]: Invalid parameter number` khi click vào `👁️ Xem` một số học viên trong bảng `🔄 Chi tiết thay đổi GV`.

## Nguyên nhân
- `array_unique()` trong PHP giữ nguyên key gốc khi loại bỏ phần tử trùng lặp, dẫn đến mảng có key không liên tục (ví dụ: `[0 => 1301, 1 => 1112, 3 => 1045]`).
- Khi truyền mảng này vào `DB::select()` với positional `?` placeholders, PDO cố bind parameter ở vị trí 4 (key 3+1) nhưng chỉ có 3 placeholder, gây ra lỗi `HY093`.

## Giải pháp
- Bọc thêm `array_values()` bên ngoài `array_unique()` để re-index mảng về key tuần tự (0, 1, 2, ...).

## File thay đổi
- `src/app/Services/DashboardService.php` — line ~8712: `array_values(array_unique(...))` thay vì `array_unique(...)`