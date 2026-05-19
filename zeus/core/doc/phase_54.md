# Phase 54 - Fix OCI cgroup Error & Container Restart Loop

## Vấn đề
Khi deploy lên server, gặp lỗi OCI runtime và container restart loop:
```
OCI runtime exec failed: ... cgroup.procs: no such file or directory
Error response from daemon: Container ... is restarting, wait until the container is running
```

## Nguyên nhân gốc
1. **Stale container ID**: Khi Docker daemon restart hoặc container bị lỗi, container ID cũ trở nên "stale" nhưng vẫn tồn tại trong cgroup system
2. **Không force recreate**: `docker compose up -d --build` giữ lại container cũ nếu có thể, dẫn đến OCI error
3. **Wait function không đủ robust**: Không verify container thực sự responsive trước khi exec

## Giải pháp triệt để

### 1. Force remove tất cả containers trước khi deploy
```bash
# Stop và remove TOÀN BỘ zeus-dashboard containers
for container in zeus-dashboard-app zeus-dashboard-nginx zeus-dashboard-redis zeus-dashboard-queue zeus-dashboard-mysql; do
    $DOCKER_CMD stop "$container" 2>/dev/null || true
    $DOCKER_CMD rm -f "$container" 2>/dev/null || true
done
sleep 3  # Cho Docker daemon dọn dẹp cgroup
```

### 2. Sử dụng --force-recreate
```bash
$COMPOSE_CMD up -d --build --force-recreate
```

### 3. Improved wait_for_container function
- Tăng timeout từ 60s lên 120s
- Verify container responsive bằng `docker exec ... echo "ping"`
- Kiểm tra exit code khi container exited

### 4. Thêm docker_exec_retry function
Retry docker exec khi gặp transient errors (OCI, restarting)

## Files thay đổi
- `DEPLOY-SERVER.sh` - Triệt để fix container cleanup và wait logic