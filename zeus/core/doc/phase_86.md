# Phase 86

## Yêu cầu
- Bổ sung thêm, user `giangdth2` và `nhaidh` được phép thấy các chỉ số về doanh thu.

## Thực hiện
- Thêm constant `REVENUE_USERNAMES` chứa `['giangdth2', 'nhaidh']` trong `AuthController`
- Tách logic: `PRIVILEGED_USERNAMES` vẫn giữ quyền xem SQL + revenue, `REVENUE_USERNAMES` chỉ xem revenue
- `$canViewRevenue` giờ check cả 2 danh sách

## Trạng thái: HOÀN THÀNH ✅
