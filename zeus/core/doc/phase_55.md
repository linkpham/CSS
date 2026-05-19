# Phase 55 - Fix Docker BuildKit Snapshot Corruption Error

## Vấn đề

DEPLOY-SERVER.sh bị lỗi khi build Docker image:

```
ERROR: failed to prepare extraction snapshot "extract-xxx sha256:...": 
parent snapshot sha256:81a5590d89b394d4c65c3df5f4f38b732a2026d8286d50ca883ba6cc8b418ce7 does not exist: not found
```

## Nguyên nhân

Docker BuildKit cache bị corrupted - các snapshot layer được cache nhưng parent snapshot đã bị xóa hoặc hỏng, dẫn đến lỗi khi build.

## Giải pháp

Thêm lệnh clear Docker build cache trước khi build:

```bash
$DOCKER_CMD builder prune -f 2>/dev/null || true
```

## Thay đổi

### DEPLOY-SERVER.sh
- Thêm `docker builder prune -f` để xóa BuildKit cache trước khi build containers
- Đặt sau khi stop/remove containers và trước khi build

## Kết quả

Build cache được clear, tránh lỗi "parent snapshot does not exist" và đảm bảo build thành công.