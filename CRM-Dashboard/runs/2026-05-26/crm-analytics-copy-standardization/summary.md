# CRM analytics copy standardization

Date: 2026-05-26
Production: `https://crm.icanwork.vn`

## Objective
Tiếp tục chuẩn hóa copy còn lẫn Anh-Việt hoặc thiên kỹ thuật trong CRM root, tập trung vào:
- analytics / command-center copy
- note filter và coverage wording
- preset labels / action copy
- một số table title, button label và status text

## Main changes
### Analytics / Command Center
Refined nhiều copy user-facing sang tiếng Việt business-friendly hơn:
- `Onboarding` -> `Khởi động`
- `Hotspot` -> `Điểm nghẽn`
- `sales/care` -> `bán hàng/chăm sóc`
- `usage` -> `mức sử dụng`
- `retention` -> `giữ chân`
- `outcome` -> `kết quả học tập`
- `live success` / `low pace` / `bad movement` -> các wording Việt hóa theo ngữ cảnh
- `Mở dashboard` -> `Xem bảng`

### LCMS wording
- `Proxy pace` -> `Chỉ báo thay thế`
- `HW / Test` -> `BTVN / BKT`
- `Nguồn LCMS thật từ Zeus` -> `Dữ liệu LCMS thật từ Zeus`

### Coverage / status / filter notes
- `coverage` -> `độ phủ dữ liệu`
- `scope` visible -> `phạm vi`
- `legacy Data_Model` visible -> `dữ liệu lịch sử`
- `dữ liệu nền movement` -> `dữ liệu nền`

### Other visible copy
- `Forecast theo tệp sức khỏe` -> `Dự báo theo tệp sức khỏe`
- `Ticket module` -> `Phân hệ Ticket`
- `Xem popup` already removed earlier; maintained
- `Mở Trung tâm điều hành` -> `Xem Trung tâm điều hành`
- package history empty-state / status text now uses `xem lịch sử` wording

## Verification
### Local/static markers
Confirmed present:
- `Dự báo theo tệp sức khỏe`
- `Điểm nghẽn khởi động`
- `BTVN`
- `BKT`
- `Tăng mức sử dụng / tránh rời bỏ âm thầm`
- `Khởi động chưa học buổi đầu`
- `Tỷ lệ buổi live thấp`
- `Dữ liệu LCMS thật từ Zeus`
- `Phân hệ Ticket`
- `Xem Trung tâm điều hành`

Confirmed absent:
- `Forecast theo tệp sức khỏe`
- `Ticket module`
- `Onboarding blocked`
- `Onboarding hotspot`
- `Onboarding hiện tại`
- `Onboarding chưa học buổi đầu`
- `Proxy pace`
- `Xem popup`
- `Mở dashboard`
- `coverage ở kỳ chọn`
- `case management riêng`
- `sales/care`

### Production HTML
Verified same markers live on `crm.icanwork.vn` after deploy.

## Result
CRM root hiện sạch hơn về copy analytics và wording kỹ thuật, đặc biệt ở các vùng người dùng đọc nhiều như Command Center, ghi chú filter, LCMS notes và preset/action labels.
