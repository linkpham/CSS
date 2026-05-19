# Phase 42 - Fix 524 Timeout on Export Usage Report

## Vấn đề

Export báo cáo sử dụng vẫn gặp lỗi 524 (Cloudflare timeout):

```
POST https://dashboard.icanwork.vn/api/start-export-usage-report 524
Failed to parse response: SyntaxError: Unexpected token '<', "<!DOCTYPE "... is not valid JSON
Export error: Error: Lỗi server (524): Không thể xử lý phản hồi
```

**Nguyên nhân gốc rễ**:
- Phase 41 đã thêm queue worker container nhưng quên cập nhật `QUEUE_CONNECTION` từ `sync` sang `redis`
- Khi `QUEUE_CONNECTION=sync`, tất cả jobs chạy đồng bộ trong HTTP request
- Cloudflare timeout sau 100 giây → trả về HTML error page thay vì JSON
- Frontend nhận HTML thay vì JSON → lỗi parse JSON

## Giải pháp

### 1. Cập nhật .env và Deployment Scripts

**Thay đổi `QUEUE_CONNECTION` từ `sync` sang `redis`** trong:

| File | Mô tả |
|------|-------|
| `src/.env` | File môi trường hiện tại |
| `DEPLOY-LOCAL.sh` | Script triển khai local |
| `DEPLOY-SERVER.sh` | Script triển khai production |

```diff
- QUEUE_CONNECTION=sync
+ QUEUE_CONNECTION=redis
```

### 2. Thêm Queue Worker vào Production Docker Compose

**File**: `docker-compose.prod.yml`

Thêm queue worker container (đã có trong `docker-compose.yml` nhưng thiếu trong prod):

```yaml
# Queue Worker for background jobs (Phase 42)
queue:
  build:
    context: .
    dockerfile: docker/php/Dockerfile
  container_name: zeus-dashboard-queue
  restart: unless-stopped
  working_dir: /var/www
  extra_hosts:
    - "host.docker.internal:host-gateway"
  volumes:
    - ./src:/var/www
    - ./docker/php/local.ini:/usr/local/etc/php/conf.d/local.ini
  command: php artisan queue:work redis --sleep=3 --tries=1 --timeout=600 --memory=512
  networks:
    - zeus-dashboard-network
  depends_on:
    - app
    - redis
```

## Kết quả

| Trước Phase 42 | Sau Phase 42 |
|----------------|--------------|
| `QUEUE_CONNECTION=sync` | `QUEUE_CONNECTION=redis` |
| Jobs chạy đồng bộ trong HTTP request | Jobs chạy background trong queue worker |
| Timeout 524 sau 100s | Trả về export_id ngay lập tức |
| Frontend không thể parse response | Frontend nhận JSON và poll tiến trình |

## Kiểm chứng

```bash
# 1. Khởi động lại containers với queue worker
docker-compose down
docker-compose up -d --build

# 2. Kiểm tra queue worker đang chạy
docker ps | grep zeus-dashboard-queue

# 3. Xem logs của queue worker
docker logs -f zeus-dashboard-queue

# 4. Test export (vào Revenue page → Xuất CSV)
# Request phải trả về ngay lập tức với export_id
# Queue worker sẽ xử lý export trong background
```

## Files Changed

1. `src/.env` - Đổi QUEUE_CONNECTION sang redis
2. `DEPLOY-LOCAL.sh` - Đổi QUEUE_CONNECTION sang redis
3. `DEPLOY-SERVER.sh` - Đổi QUEUE_CONNECTION sang redis
4. `docker-compose.prod.yml` - Thêm queue worker container
