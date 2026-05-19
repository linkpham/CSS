# Phase 96

## Yêu cầu
- Hãy đọc hướng dẫn `Lấy chi tiết danh sách tin nhắn chat` để làm sao lấy được toàn bộ các tin nhắn chat. Cần viết chạy test trước khi tích hợp vào dashboard.

## Phân tích API

### Endpoint
`GET {{domain}}/api/v1/chats/messages`

### Tham số

| Tham số | Bắt buộc | Mô tả |
|---------|----------|-------|
| `start_time_since` | ✅ | Thời gian bắt đầu (ISO8601: `yyyy-MM-ddTHH:mm:ssZ`) |
| `start_time_to` | | Thời gian kết thúc (ISO8601), mặc định = hiện tại |
| `conversation_type` | | 0=LiveChat (mặc định), 1=Messenger/Instagram, 3=Zalo |
| `conversation_id` | | ID phiên chat (bỏ qua conversation_type nếu có) |
| `requester_id` | | ID khách hàng |
| `last_agent_user_id` | | ID chuyên viên xử lý |
| `services` | | ID dịch vụ |
| `count` | | Số bản ghi/trang (tối đa 500, mặc định 50) |
| `page` | | Số trang (mặc định 1) |

### Giới hạn quan trọng
- **Max 30 ngày** giữa `start_time_since` và `start_time_to` (API trả 400 nếu quá)
- **Max 500 records/request** (pagination qua `page` + `count`)
- **Phải truyền `conversation_type`** để lấy các loại chat khác ngoài LiveChat (mặc định = 0)
- `numFound` trong response cho biết tổng số bản ghi thỏa mãn điều kiện

### Cấu trúc response

```json
{
    "code": "ok",
    "numFound": 18,
    "chats": [
        {
            "conversation_id": "...",
            "id": 799734999,
            "msg_id": "...",
            "message_index": 2,
            "content": "Nội dung tin nhắn",
            "time": "2023-08-22 13:55:19",
            "service_id": 62062484,
            "start_time": "2023-08-22 13:54:51",
            "sender_agent_name": null,
            "sender_agent_id": null,
            "sender_visitor_name": null,
            "sender_visitor_id": null,
            "last_agent_user_id": 124734559,
            "ticket_id": 421360514,
            "type": 3,
            "conversation_type": 3,
            "requester_id": 174137179,
            "oa_name": "CareSoft Test",
            "oa_id": "1600195475413752846"
        }
    ]
}
```

### Chiến lược lấy toàn bộ tin nhắn

1. **Chia window thời gian**: Nếu lookback > 30 ngày → chia thành chunks 30 ngày
2. **Lặp qua 3 conversation types**: 0 (LiveChat), 1 (Messenger/Instagram), 3 (Zalo)
3. **Phân trang**: Mỗi chunk, lặp `page=1,2,3...` với `count=500` cho đến khi hết `numFound`
4. **Rate limiting**: Delay 200ms giữa mỗi API call
5. **Retry + backup host**: Nếu server lỗi → thử `api2.caresoft.vn`

## Kết quả

### Test script đã tạo

**File:** `scripts/test-chat-messages.php`

Script standalone PHP test thực hiện:
1. Đọc `.env` để lấy `CARESOFT_DOMAIN` và `CARESOFT_API_TOKEN`
2. Kiểm tra kết nối API (thử gọi agents endpoint)
3. Fetch tất cả tin nhắn chat qua 3 conversation types
4. Handle pagination (500/page) và window chunking (30 ngày)
5. Validate cấu trúc dữ liệu response
6. Thống kê: loại nội dung, phân loại người gửi, số conversations
7. Nếu không có token → chạy dry-run hiển thị kế hoạch fetch

### Chạy test

```bash
# Dry-run (không cần token) - hiển thị kế hoạch fetch
docker exec zeus-dashboard-app php /tmp/test-chat-messages.php --days=7

# Với token - fetch dữ liệu thực
# (Cần set CARESOFT_API_TOKEN trong src/.env trước)
docker cp scripts/test-chat-messages.php zeus-dashboard-app:/tmp/ && \
docker exec zeus-dashboard-app php /tmp/test-chat-messages.php --days=7

# Chỉ test 1 loại chat
docker exec zeus-dashboard-app php /tmp/test-chat-messages.php --days=7 --type=1

# Với verbose output
docker exec zeus-dashboard-app php /tmp/test-chat-messages.php --days=7 --verbose

# Giới hạn số tin nhắn (để test nhanh)
docker exec zeus-dashboard-app php /tmp/test-chat-messages.php --days=30 --limit=50
```

### Kết quả dry-run (không có token)

```
═══ Test: Lấy chi tiết danh sách tin nhắn chat (CareSoft API) ═══
[INFO] Domain: zeusedu
[INFO] Lookback: 7 ngày
[INFO] Conversation types: 0 (LiveChat), 1 (Messenger/Instagram), 3 (Zalo)

═══ DRY RUN: Kế hoạch fetch API ═══
  [LiveChat]
    Chunk 1: 2026-02-04T00:00:00Z → 2026-02-11T...Z
      URL: https://api.caresoft.vn/zeusedu/api/v1/chats/messages
      Params: start_time_since=...&conversation_type=0&count=500&page=1
  [Messenger/Instagram]
    Chunk 1: ...
  [Zalo]
    Chunk 1: ...

[OK] Kế hoạch fetch đã được xác nhận chính xác
```

### Tích hợp hiện có trong Dashboard

Code tích hợp đã sẵn sàng trong codebase:
- `CareSoftApiClient::getChatMessages()` - API client method
- `CareSoftSync::syncChatMessages()` - Artisan command sync
- `CareSoftService::getChatMessageStats()` - Dashboard stats
- Table `cs_chat_messages` - SQLite cache
- Auto-classify messages bằng keyword matching

Chạy sync thực tế:
```bash
docker exec zeus-dashboard-app php artisan caresoft:sync --type=messages --days=7
```

## Status: ✅ HOÀN THÀNH
