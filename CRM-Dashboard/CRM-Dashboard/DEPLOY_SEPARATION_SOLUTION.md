# Giải pháp tách Nginx riêng cho CRM

## Vấn đề

- Zeus dashboard deploy ghi đè cấu hình CRM
- Cả 2 dịch vụ đang dùng chung container `zeus-dashboard-nginx`
- Mỗi lần Zeus deploy → CRM mất config hoặc bị redirect loop

## Giải pháp đã triển khai (v1 - Tạm thời)

**Trạng thái hiện tại:**
- CRM đã có Nginx container riêng (`icc-crm-nginx`)
- Port mapping: 8082 (HTTP), 4443 (HTTPS)
- **NHƯNG:** Cloudflare vẫn trỏ vào zeus-dashboard-nginx port 80/443
- Config CRM vẫn nằm trong `/var/www/zeus-dashboard/docker/nginx/conf.d/icc-crm.conf`

**Lý do chưa chuyển hoàn toàn:**
- Port 8082/4443 chưa được mở trong AWS Security Group
- Cần access AWS Console để thay đổi

## Giải pháp hoàn chỉnh (v2 - Khuy ến nghị)

### Bước 1: Mở port trong AWS Security Group

1. Truy cập AWS EC2 Console
2. Chọn instance `13.215.57.82`
3. Security Groups → Edit inbound rules
4. Thêm rules:
   - Type: Custom TCP
   - Port: 8082
   - Source: 0.0.0.0/0 (hoặc chỉ Cloudflare IPs)
   - Description: CRM HTTP
   
   - Type: Custom TCP
   - Port: 4443
   - Source: 0.0.0.0/0
   - Description: CRM HTTPS

### Bước 2: Cập nhật Cloudflare Origin

**Hiện tại:**
```
crm.icanwork.vn → Cloudflare → 13.215.57.82:80/443 (zeus-dashboard-nginx)
```

**Sau khi đổi:**
```
crm.icanwork.vn → Cloudflare → 13.215.57.82:8082/4443 (icc-crm-nginx)
dashboard.icanwork.vn → Cloudflare → 13.215.57.82:80/443 (zeus-dashboard-nginx)
```

**Các bước:**
1. Cloudflare Dashboard → DNS
2. Tìm record `crm.icanwork.vn`
3. Edit record:
   - Type: A
   - Name: crm
   - Target: 13.215.57.82
   - Proxy: ON (orange cloud)
4. Cloudflare → SSL/TLS → Origin Server
5. Tạo origin server rule:
   - Hostname: crm.icanwork.vn
   - Port: 8082 (HTTP), 4443 (HTTPS)

**Hoặc dùng Cloudflare Tunnel (nâng cao):**
- Không cần mở port public
- Cloudflare Tunnel tạo kết nối outbound từ server

### Bước 3: Xóa config CRM khỏi Zeus nginx

```bash
ssh quenn@13.215.57.82
sudo rm /var/www/zeus-dashboard/docker/nginx/conf.d/icc-crm.conf
docker exec zeus-dashboard-nginx nginx -s reload
```

### Bước 4: Verify

```bash
# Test CRM port trực tiếp
curl http://13.215.57.82:8082/

# Test qua Cloudflare
curl https://crm.icanwork.vn/
```

## Lợi ích

✅ **Độc lập hoàn toàn:** Zeus deploy không ảnh hưởng CRM  
✅ **Dễ troubleshoot:** Mỗi service có log riêng  
✅ **Dễ scale:** Có thể chuyển CRM sang server khác dễ dàng  
✅ **Config rõ ràng:** Không bị coupling giữa 2 dự án

## Rollback (nếu cần)

Nếu có vấn đề, restore lại config cũ:

```bash
ssh quenn@13.215.57.82
cat > /var/www/zeus-dashboard/docker/nginx/conf.d/icc-crm.conf << 'EOF'
# [copy nội dung config đã backup]
EOF
docker exec zeus-dashboard-nginx nginx -s reload

# Stop CRM nginx container
cd /var/www/icc-crm
docker-compose stop nginx
```

## Tham khảo

- Docker compose: `CRM-Dashboard/CRM-Dashboard/docker-compose.yml`
- Nginx config: `CRM-Dashboard/CRM-Dashboard/nginx/conf.d/crm.conf`
- Zeus deploy: `zeus/core/DEPLOY-SERVER.sh`
- CRM deploy: `DEPLOY_SERVER.sh`
