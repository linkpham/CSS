# Phase 34 - Fix Acceptance Code Stats "Tuần trước" Tab

## Vấn đề
- Trong `📋 Mã Acceptance Code (Mã kết quả ca học)`, tại phần `📊 Thống kê theo mã Acceptance Code` khi nhấn vào `Tuần trước` không thấy ra dữ liệu.

## Nguyên nhân
- Tab button cho "Tuần trước" (`activeCodeTab = 'last_week'`) đã tồn tại trong giao diện
- Tuy nhiên, không có content section tương ứng với `x-show="activeCodeTab === 'last_week'"` để hiển thị dữ liệu
- Backend (`DashboardService.php`) đã cung cấp dữ liệu `last_week` đầy đủ

## Giải pháp
- Thêm section hiển thị cho tab "Tuần trước" trong `src/resources/views/dashboard/index.blade.php`
- Section mới bao gồm:
  - 4 cards thống kê (Tổng hoàn thành, Thành công Code 12, Không thành công, Tỷ lệ thành công)
  - Bảng chi tiết theo từng Acceptance Code với số lượng và tỷ lệ
  - SQL tooltips cho mỗi metric

## Thay đổi
- `src/resources/views/dashboard/index.blade.php`: Thêm 58 dòng cho section "Last Week Stats (Tuần trước)" 

## Kiểm tra
- ✅ Chạy `./DEPLOY-LOCAL.sh upgrade` thành công
- ✅ Cache đã được clear
- ✅ Section mới đã được deploy vào container

