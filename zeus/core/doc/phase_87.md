# Phase 87 - CareSoft CSKH Dashboard

## Yêu cầu
Xây dựng trang quản trị vận hành và chất lượng CSKH dựa trên dữ liệu CareSoft, đảm bảo số liệu đúng, cập nhật kịp thời và dùng được để điều hành, đánh giá chất lượng phản hồi và chất lượng nội dung xử lý.

## Kết quả thực hiện ✅

### 1. Cấu trúc files đã tạo

```
src/
├── app/
│   ├── Console/Commands/
│   │   └── CareSoftSync.php          # Artisan command đồng bộ dữ liệu
│   ├── Http/Controllers/
│   │   └── CareSoftController.php    # Controller xử lý routes và APIs
│   └── Services/
│       ├── CareSoftApiClient.php     # API client gọi CareSoft API
│       └── CareSoftService.php       # Business logic dashboard metrics
├── config/
│   ├── caresoft.php                  # Config CareSoft API credentials
│   └── database.php                  # Thêm SQLite connection cho cache
├── resources/views/
│   └── caresoft/
│       └── index.blade.php           # Dashboard UI với charts
└── routes/
    └── web.php                       # Routes cho CareSoft dashboard
```

### 2. Tính năng đã hoàn thành

#### API Client (`CareSoftApiClient.php`)
- Hỗ trợ host chính và host backup (api.caresoft.vn / api2.caresoft.vn)
- Auto retry với exponential backoff khi gặp lỗi
- Rate limiting delay để tránh throttle
- Pagination helper để lấy toàn bộ dữ liệu
- Endpoints: agents, groups, services, tickets, calls, chats

#### Data Sync (`CareSoftSync.php`)
- Command: `php artisan caresoft:sync`
- Options: `--type=all|agents|groups|services|tickets|calls|chats`
- Options: `--days=7` (số ngày lấy về cho incremental sync)
- Options: `--full` (force full sync từ đầu)
- Tự động tạo tables SQLite nếu chưa có
- updateOrInsert để không duplicate dữ liệu
- Chia nhỏ theo window 31 ngày để tránh timeout
- Log sync status vào cs_sync_logs

#### Dashboard Metrics (`CareSoftService.php`)
- **Agent Status**: Tổng agent, online theo kênh (call/ticket/chat), theo nhóm
- **Ticket Stats**: Tổng phiếu, theo status, theo nguồn, theo priority, CSAT
- **Call Stats**: Tổng cuộc gọi, inbound/outbound, met/missed, tỷ lệ gặp, lý do nhỡ
- **Chat Stats**: Tổng chat, theo kênh (livechat/facebook/zalo), tỷ lệ gặp, thời lượng TB
- **Sync Status**: Trạng thái đồng bộ gần nhất của từng loại dữ liệu

#### Dashboard UI (`index.blade.php`)
- **Agent Status Cards**: Realtime-lite với nút refresh live từ API
- **KPI Cards**: Tickets, Calls, Chats với breakdown chi tiết
- **Charts**:
  - Doughnut: Phiếu ghi theo nguồn
  - Line: Xu hướng cuộc gọi (met/missed)
  - Bar: Cuộc gọi theo giờ trong ngày
  - Line: Xu hướng chat (met/missed)
- **Tables**:
  - Agent status theo nhóm
  - Lý do nhỡ cuộc gọi
  - Top 10 agent chat
- **Sync Status Panel**: Hiển thị trạng thái đồng bộ từng loại dữ liệu
- **Date Range Filter**: Hôm nay, hôm qua, tuần này, tháng này

#### Routes & Navigation
- Web: `/caresoft` - Dashboard chính
- API: `/api/caresoft/agent-status` - Realtime agent status
- API: `/api/caresoft/summary` - Dashboard summary data
- API: `/api/caresoft/tickets|calls|chats` - Detailed stats
- Sidebar: Thêm mục "CSKH" với icon 🎧

### 3. Database Schema (SQLite Cache)

```sql
-- cs_agents: Agent info và trạng thái
-- cs_groups: Danh sách bộ phận
-- cs_services: Danh sách dịch vụ
-- cs_tickets: Phiếu ghi/ticket
-- cs_calls: Lịch sử cuộc gọi
-- cs_chats: Lịch sử chat
-- cs_sync_logs: Log đồng bộ
```

### 4. Cách sử dụng

```bash
# Thiết lập env variables
CARESOFT_DOMAIN=GalaxyHN
CARESOFT_API_TOKEN=<token>

# Đồng bộ tất cả dữ liệu (7 ngày gần nhất)
docker exec -it zeus-dashboard-app php artisan caresoft:sync

# Đồng bộ 30 ngày
docker exec -it zeus-dashboard-app php artisan caresoft:sync --days=30

# Chỉ đồng bộ agents (realtime)
docker exec -it zeus-dashboard-app php artisan caresoft:sync --type=agents

# Full sync từ đầu (365 ngày)
docker exec -it zeus-dashboard-app php artisan caresoft:sync --full

# Truy cập dashboard
http://localhost:8080/caresoft
```

### 5. Giải quyết các vấn đề yêu cầu

| Vấn đề | Giải pháp |
|--------|-----------|
| Dữ liệu mới nhất | Incremental sync theo updated_at/start_time |
| Không trùng lặp | updateOrInsert với unique key |
| Phân trang API | getAllPages helper với progress callback |
| Chịu lỗi | Auto retry, backup host, log errors |
| Độ tươi khác nhau | Agent status realtime, others sync định kỳ |
| Đa kênh | Phân loại theo service_type/conversation_type |
| Bảo mật | API token từ env, không log token |
| Quan sát hệ thống | cs_sync_logs với elapsed_seconds và error |

## Status: ✅ COMPLETED