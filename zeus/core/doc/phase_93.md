# Phase 93

## Yêu cầu
- Sửa lỗi biểu đồ Phiếu thi theo nguồn bị hỏng, nhiều biểu đồ đè lên nhau (chỉ khi nào chọn item trên dropdownlist Hôm nay, hôm qua thì  mới hết)
- Lấy toàn bộ các đoạn Chat về và phân loại. Toàn bộ phải tự động hóa với người dùng phổ thông, không được sai sót.

## Kết quả

### 1. Fix lỗi biểu đồ "Phiếu thi theo nguồn" chồng lên nhau

**Nguyên nhân:** Khi thay đổi range (Hôm nay, Hôm qua, Tuần này, Tháng này), các function render chart không destroy chart cũ trước khi tạo mới nếu data trống.

**Giải pháp:** Đã cập nhật các function `renderDoughnut()`, `renderLineTrend()`, `renderBarChart()` trong `caresoft/index.blade.php`:
- Luôn gọi `destroyChart(id)` trước khi render chart mới
- Nếu không có data, xóa canvas và hiển thị text "Chưa có dữ liệu" thay vì bỏ qua

### 2. Lấy toàn bộ đoạn Chat và phân loại tự động

**Tính năng mới:**

#### a) API Endpoint mới
- `GET /api/caresoft/chat-messages` - Lấy thống kê tin nhắn chat với phân loại

#### b) Sync command mở rộng
- Thêm type `messages` vào command `caresoft:sync`
- Sử dụng: `php artisan caresoft:sync --type=messages`
- Tự động đồng bộ tin nhắn chat chi tiết từ CareSoft API (`GET /api/v1/chats/messages`)

#### c) Bảng mới `cs_chat_messages`
Lưu trữ chi tiết tin nhắn chat với các trường:
- `msg_id`, `conversation_id`, `conversation_type`
- `content`, `type` (1=text, 2=file, 3=system, 4=template)
- `sender_agent_name`, `sender_visitor_name`
- `category` - Phân loại tự động

#### d) Phân loại tự động (Auto-classification)
Tin nhắn được phân loại tự động dựa trên nội dung:

**Từ khách hàng:**
| Category | Mô tả | Từ khóa |
|----------|-------|---------|
| `inquiry` | Hỏi/Thắc mắc | hỏi, thắc mắc, tư vấn, giá, bao nhiêu, như thế nào... |
| `complaint` | Khiếu nại/Phản ánh | khiếu nại, không hài lòng, lỗi, sai, thất vọng... |
| `order` | Đặt hàng/Mua | đặt hàng, mua, order, thanh toán, giao hàng... |
| `feedback` | Góp ý/Đánh giá | góp ý, đánh giá, review, cảm ơn, tốt, tuyệt vời... |
| `support` | Yêu cầu hỗ trợ | hỗ trợ, giúp, help, support... |
| `greeting` | Chào hỏi | xin chào, hello, hi, chào... |
| `customer_message` | Tin nhắn khác | (không khớp các loại trên) |

**Từ Agent:**
| Category | Mô tả |
|----------|-------|
| `agent_greeting` | Agent chào |
| `agent_solution` | Agent hướng dẫn/giải quyết |
| `agent_response` | Agent trả lời |

**Hệ thống:**
| Category | Mô tả |
|----------|-------|
| `system` | Tin nhắn hệ thống (type=3) |
| `attachment` | File đính kèm (type=2) |
| `template` | Template (type=4) |

#### e) UI Dashboard
Thêm section "Phân loại tin nhắn Chat" hiển thị:
- Tổng tin nhắn, tin từ khách hàng, tin từ agent, tin hệ thống
- Breakdown theo loại tin nhắn khách hàng
- Phân loại theo kênh (LiveChat, Facebook/Instagram, Zalo)

### Files đã thay đổi
- `src/resources/views/caresoft/index.blade.php` - Fix chart + thêm UI phân loại chat
- `src/app/Services/CareSoftApiClient.php` - Thêm `getChatMessages()` method
- `src/app/Console/Commands/CareSoftSync.php` - Thêm `syncChatMessages()` và `classifyMessage()`
- `src/app/Services/CareSoftService.php` - Thêm `getChatMessageStats()`
- `src/app/Http/Controllers/CareSoftController.php` - Thêm `apiChatMessages()`
- `src/routes/web.php` - Thêm route `/api/caresoft/chat-messages`

### Cách sử dụng

1. **Đồng bộ tin nhắn chat:**
```bash
# Đồng bộ tin nhắn 7 ngày gần nhất
docker exec zeus-dashboard-app php artisan caresoft:sync --type=messages

# Đồng bộ 30 ngày gần nhất
docker exec zeus-dashboard-app php artisan caresoft:sync --type=messages --days=30

# Đồng bộ tất cả (bao gồm messages)
docker exec zeus-dashboard-app php artisan caresoft:sync
```

2. **Xem phân loại trên Dashboard:**
- Truy cập trang CareSoft Dashboard
- Scroll xuống section "Phân loại tin nhắn Chat"
- Nhấn "Tải dữ liệu" để xem thống kê

## Status: ✅ HOÀN THÀNH