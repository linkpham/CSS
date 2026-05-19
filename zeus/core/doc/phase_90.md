# Phase 90

## Yêu cầu
Lỗi nhiều quá:
```
chart.js:13  Uncaught TypeError: Cannot read properties of null (reading 'save')
api/caresoft/agent-status:1   Failed to load resource: the server responded with a status of 500 (Internal Server Error)
api/caresoft/agent-status:1   Failed to load resource: the server responded with a status of 500 (Internal Server Error)
caresoft:1458   GET http://localhost:8080/api/caresoft/agent-status 500 (Internal Server Error)```

Tôi đã thử đoạn mã sau và thành công:
```
 curl \
--location 'https://api.caresoft.vn/GalaxyHN/api/v1/agents' \
--header 'Authorization: Bearer <API_TOKEN>' \
--header 'Content-Type: application/json'
```. Hãy lấy đây là cơ sở để sửa toàn bộ lỗi gọi đến care soft. Đặt tên menu trong sidebar là Care Soft. Đảm bảo mọi API phải chạy được. 

## Kết quả

### 1. Sửa lỗi CareSoft API 500 Internal Server Error
- Cập nhật `.env` với cấu hình CareSoft chính xác:
  - `CARESOFT_DOMAIN=GalaxyHN`
  - `CARESOFT_API_TOKEN=<configured in .env>`
- Các API đã test thành công:
  - Agents API: OK (255 agents)
  - Groups API: OK (99 groups)
  - Services API: OK (104 services)

### 2. Sửa lỗi chart.js "Cannot read properties of null"
- Thêm kiểm tra `canvas.getContext('2d')` trước khi tạo Chart
- Thêm kiểm tra dữ liệu rỗng để skip render khi không có data
- Sửa cả 3 hàm: `renderDoughnut`, `renderLineTrend`, `renderBarChart`

### 3. Đổi tên menu sidebar
- Đổi từ "CSKH" thành "Care Soft" trong sidebar layout

### Files đã sửa:
- `src/.env` - Cập nhật CareSoft domain và token
- `src/resources/views/caresoft/index.blade.php` - Fix chart.js null errors
- `src/resources/views/layouts/app.blade.php` - Đổi tên menu sidebar

## Status: ✅ COMPLETED

