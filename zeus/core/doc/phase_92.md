# Phase 92

## Yêu cầu
- Không được bắt người dùng phải chạy `docker exec -it zeus-dashboard-app php artisan caresoft:sync` để đồng bộ
- Hiện nay dữ liệu toàn trả về 0 hết như thế này: 
```
Syncing agents...
  -> 0 records synced in 1.51s
Syncing groups...
  -> 0 records synced in 1.39s
Syncing services...
  -> 0 records synced in 1.33s
Syncing tickets...

  -> 0 records synced in 1.31s
Syncing calls...

  -> 0 records synced in 1.19s
Syncing chats...
```
Kiểm tra xem có sai sót gì không. Cung cấp lệnh gọi curl để đối chiếu.

## Kết quả

### Nguyên nhân lỗi
1. **CARESOFT_API_TOKEN trống** trong `.env` - không có API token nên tất cả request đều thất bại (401 Unauthorized)
2. **CARESOFT_DOMAIN sai** - cấu hình là `zeusedu` nhưng domain đúng là `GalaxyHN`

### Các thay đổi đã thực hiện

#### 1. Cập nhật `.env` với thông tin đăng nhập đúng
```env
CARESOFT_DOMAIN=GalaxyHN
CARESOFT_API_TOKEN=<your-api-token>
```
> **Lưu ý:** Token được cấu hình trong CareSoft Admin → API → API Token

#### 2. Thêm đồng bộ tự động vào Scheduler (`app/Console/Kernel.php`)
- Đồng bộ incremental mỗi 30 phút (1 ngày lookback)
- Đồng bộ đầy đủ hàng tuần vào Chủ nhật 2:00 AM (30 ngày lookback)

#### 3. Thêm auto-sync khi truy cập trang CareSoft
- Tạo job `CareSoftInitialSync` chạy background queue
- Khi vào trang CareSoft lần đầu (chưa có data), hệ thống tự động trigger sync
- Không cần chạy lệnh thủ công nữa

#### 4. Thêm UI buttons cho đồng bộ
- Nút "Đồng bộ ngay" trên banner cảnh báo khi chưa có dữ liệu
- Nút "Kiểm tra kết nối API" để test connection
- Nút "Đồng bộ ngay" ở phần Sync Status
- Hiển thị trạng thái đang syncing với polling 10s

#### 5. Thêm API endpoints mới
- `POST /api/caresoft/trigger-sync` - trigger đồng bộ thủ công
- `GET /api/caresoft/test-connection` - test kết nối API CareSoft

### Lệnh curl để kiểm tra API

> **Thay thế** `{{DOMAIN}}` bằng `GalaxyHN` và `{{TOKEN}}` bằng API token thực tế từ CareSoft.

#### Lấy danh sách Agents
```bash
curl --location 'https://api.caresoft.vn/{{DOMAIN}}/api/v1/agents' \
--header 'Authorization: Bearer {{TOKEN}}' \
--header 'Content-Type: application/json'
```

#### Lấy danh sách Groups (Bộ phận)
```bash
curl --location 'https://api.caresoft.vn/{{DOMAIN}}/api/v1/groups' \
--header 'Authorization: Bearer {{TOKEN}}' \
--header 'Content-Type: application/json'
```

#### Lấy danh sách Services (Dịch vụ)
```bash
curl --location 'https://api.caresoft.vn/{{DOMAIN}}/api/v1/services' \
--header 'Authorization: Bearer {{TOKEN}}' \
--header 'Content-Type: application/json'
```

#### Lấy Tickets (Phiếu ghi) - có phân trang
```bash
curl --location 'https://api.caresoft.vn/{{DOMAIN}}/api/v1/tickets?page=1&count=50' \
--header 'Authorization: Bearer {{TOKEN}}' \
--header 'Content-Type: application/json'
```

#### Lấy Calls (Cuộc gọi) - theo khoảng thời gian
```bash
curl --location 'https://api.caresoft.vn/{{DOMAIN}}/api/v1/calls?start_time_since=2026-02-09T00:00:00Z&start_time_to=2026-02-10T23:59:59Z&page=1&count=50' \
--header 'Authorization: Bearer {{TOKEN}}' \
--header 'Content-Type: application/json'
```

#### Lấy Chats - theo loại conversation
```bash
# Livechat (conversation_type=0)
curl --location 'https://api.caresoft.vn/{{DOMAIN}}/api/v1/chats?conversation_type=0&start_time_since=2026-02-09T00:00:00Z&page=1&count=50' \
--header 'Authorization: Bearer {{TOKEN}}' \
--header 'Content-Type: application/json'

# Facebook/Instagram (conversation_type=1)
curl --location 'https://api.caresoft.vn/{{DOMAIN}}/api/v1/chats?conversation_type=1&start_time_since=2026-02-09T00:00:00Z&page=1&count=50' \
--header 'Authorization: Bearer {{TOKEN}}' \
--header 'Content-Type: application/json'

# Zalo (conversation_type=3)
curl --location 'https://api.caresoft.vn/{{DOMAIN}}/api/v1/chats?conversation_type=3&start_time_since=2026-02-09T00:00:00Z&page=1&count=50' \
--header 'Authorization: Bearer {{TOKEN}}' \
--header 'Content-Type: application/json'
```

### Kết quả test sau khi sửa
```
Syncing agents...
  -> 255 records synced in 1.16s
Syncing groups...
  -> 99 records synced in 6.2s
Syncing services...
  -> 104 records synced in 6.68s
Syncing calls...
  -> 1657 records synced in 24.29s
```

## Status: ✅ HOÀN THÀNH

