#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-CRM-Dashboard/CRM-Dashboard}"
CONF_DIR="${CONF_DIR:-CRM-Dashboard/conf}"
SSH_KEY="${SSH_KEY:-$CONF_DIR/ssh-key}"
SSH_USER="${SSH_USER:-quenn}"
SSH_HOST="${SSH_HOST:-13.215.57.82}"
REMOTE_DIR="${REMOTE_DIR:-/var/www/icc-crm}"
DOMAIN="${DOMAIN:-crm.icanwork.vn}"
CERTBOT_EMAIL="${CERTBOT_EMAIL:-admin@icanwork.vn}"

SCRIPT_PATH="${BASH_SOURCE[0]}"
PROXY_DIR="${PROXY_DIR:-/var/www/zeus-dashboard}"
PROXY_COMPOSE_FILE="$PROXY_DIR/docker-compose.yml"
PROXY_NGINX_CONF_DIR="$PROXY_DIR/docker/nginx/conf.d"
PROXY_CERTBOT_WEBROOT="$PROXY_DIR/docker/certbot/www"
PROXY_NETWORK="${PROXY_NETWORK:-zeus-dashboard_zeus-dashboard-network}"

log() { printf '\n[deploy] %s\n' "$*"; }
die() { printf '\n[deploy:error] %s\n' "$*" >&2; exit 1; }

[ -f "$SSH_KEY" ] || die "SSH key not found: $SSH_KEY"
[ -d "$APP_DIR" ] || die "App directory not found: $APP_DIR"
chmod 600 "$SSH_KEY"

SSH_OPTS=(-i "$SSH_KEY" -o BatchMode=yes -o StrictHostKeyChecking=accept-new)
REMOTE="$SSH_USER@$SSH_HOST"

log "Sync source to $REMOTE:$REMOTE_DIR"
ssh "${SSH_OPTS[@]}" "$REMOTE" "sudo mkdir -p '$REMOTE_DIR' && sudo chown '$SSH_USER':'$SSH_USER' '$REMOTE_DIR'"
rsync -az --delete \
  --exclude node_modules \
  --exclude data \
  --exclude conf \
  --exclude '*.db' \
  --exclude '*.sqlite' \
  --exclude '*.sqlite3' \
  -e "ssh -i $SSH_KEY -o BatchMode=yes -o StrictHostKeyChecking=accept-new" \
  "$APP_DIR/" "$REMOTE:$REMOTE_DIR/"
rsync -az -e "ssh -i $SSH_KEY -o BatchMode=yes -o StrictHostKeyChecking=accept-new" \
  "$SCRIPT_PATH" "$REMOTE:$REMOTE_DIR/DEPLOY_SERVER.sh"

ssh "${SSH_OPTS[@]}" "$REMOTE" "mkdir -p '$REMOTE_DIR/conf' '$REMOTE_DIR/data'"
if [ -f "$CONF_DIR/credentials.json" ]; then
  log "Upload Google service-account credentials"
  rsync -az -e "ssh -i $SSH_KEY -o BatchMode=yes -o StrictHostKeyChecking=accept-new" \
    "$CONF_DIR/credentials.json" "$REMOTE:$REMOTE_DIR/conf/credentials.json"
else
  log "Skip $CONF_DIR/credentials.json: file not found locally. Sync job will need this later."
fi

log "Install/verify Docker, Certbot, and shared proxy prerequisites"
ssh "${SSH_OPTS[@]}" "$REMOTE" bash -s -- "$REMOTE_DIR" "$DOMAIN" "$CERTBOT_EMAIL" "$PROXY_DIR" "$PROXY_COMPOSE_FILE" "$PROXY_NGINX_CONF_DIR" "$PROXY_CERTBOT_WEBROOT" "$PROXY_NETWORK" <<'REMOTE_SETUP'
set -euo pipefail
REMOTE_DIR="$1"
DOMAIN="$2"
CERTBOT_EMAIL="$3"
PROXY_DIR="$4"
PROXY_COMPOSE_FILE="$5"
PROXY_NGINX_CONF_DIR="$6"
PROXY_CERTBOT_WEBROOT="$7"
PROXY_NETWORK="$8"

log() { printf '\n[remote] %s\n' "$*"; }
die() { printf '\n[remote:error] %s\n' "$*" >&2; exit 1; }

export DEBIAN_FRONTEND=noninteractive
if ! command -v docker >/dev/null 2>&1; then
  log "Install Docker from Ubuntu packages"
  sudo apt-get update
  sudo apt-get install -y docker.io docker-compose-plugin
  sudo systemctl enable --now docker
fi
if ! docker compose version >/dev/null 2>&1; then
  sudo apt-get update
  sudo apt-get install -y docker-compose-plugin
fi
if ! command -v certbot >/dev/null 2>&1; then
  log "Install Certbot"
  sudo apt-get update
  sudo apt-get install -y certbot
fi

docker network inspect "$PROXY_NETWORK" >/dev/null 2>&1 || die "Missing Docker proxy network: $PROXY_NETWORK"
[ -f "$PROXY_COMPOSE_FILE" ] || die "Missing proxy compose file: $PROXY_COMPOSE_FILE"
[ -d "$PROXY_NGINX_CONF_DIR" ] || die "Missing proxy nginx conf dir: $PROXY_NGINX_CONF_DIR"

log "Build/start CRM containers"
cd "$REMOTE_DIR"
docker compose up -d --build

log "Prepare proxy volumes for ACME/HTTPS"
sudo mkdir -p "$PROXY_CERTBOT_WEBROOT"
sudo chown -R "$USER":"$USER" "$PROXY_DIR/docker/certbot"
backup_dir="$PROXY_DIR/backups/icc-crm-$(date +%Y%m%d%H%M%S)"
mkdir -p "$backup_dir"
cp "$PROXY_COMPOSE_FILE" "$backup_dir/docker-compose.yml"
[ ! -f "$PROXY_NGINX_CONF_DIR/icc-crm.conf" ] || cp "$PROXY_NGINX_CONF_DIR/icc-crm.conf" "$backup_dir/icc-crm.conf"
log "Proxy backup saved to $backup_dir"

python3 - "$PROXY_COMPOSE_FILE" <<'PY'
import sys
from pathlib import Path
p=Path(sys.argv[1])
s=p.read_text()
orig=s
if '      - "443:443"' not in s and '      - 443:443' not in s:
    s=s.replace('      - "80:80"\n', '      - "80:80"\n      - "443:443"\n')
if '      - ./docker/certbot/www:/var/www/certbot' not in s:
    s=s.replace('      - ./docker/nginx/conf.d:/etc/nginx/conf.d\n', '      - ./docker/nginx/conf.d:/etc/nginx/conf.d\n      - ./docker/certbot/www:/var/www/certbot\n      - /etc/letsencrypt:/etc/letsencrypt:ro\n')
if s==orig:
    print('compose already patched')
else:
    p.write_text(s)
    print('compose patched')
PY

cat > "$PROXY_NGINX_CONF_DIR/icc-crm.conf" <<EOF
map \$http_upgrade \$icc_crm_connection_upgrade {
    default upgrade;
    '' close;
}

server {
    listen 80;
    server_name $DOMAIN;

    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }

    location / {
        proxy_pass http://icc-crm-app:3000;
        proxy_http_version 1.1;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection \$icc_crm_connection_upgrade;
        proxy_read_timeout 300;
        proxy_send_timeout 300;
    }
}
EOF

log "Reload shared Docker Nginx with CRM HTTP vhost"
cd "$PROXY_DIR"
docker compose up -d webserver
docker exec zeus-dashboard-nginx nginx -t
docker exec zeus-dashboard-nginx nginx -s reload || true

log "Request/renew Let's Encrypt certificate"
if sudo certbot certonly --webroot \
  -w "$PROXY_CERTBOT_WEBROOT" \
  -d "$DOMAIN" \
  --email "$CERTBOT_EMAIL" \
  --agree-tos \
  --non-interactive \
  --keep-until-expiring \
  --no-eff-email; then
  cat > "$PROXY_NGINX_CONF_DIR/icc-crm.conf" <<EOF
map \$http_upgrade \$icc_crm_connection_upgrade {
    default upgrade;
    '' close;
}

server {
    listen 80;
    server_name $DOMAIN;

    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }

    location / {
        if (\$http_x_forwarded_proto != "https") {
            return 301 https://\$host\$request_uri;
        }

        proxy_pass http://icc-crm-app:3000;
        proxy_http_version 1.1;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection \$icc_crm_connection_upgrade;
        proxy_read_timeout 300;
        proxy_send_timeout 300;
    }
}

server {
    listen 443 ssl;
    http2 on;
    server_name $DOMAIN;

    ssl_certificate /etc/letsencrypt/live/$DOMAIN/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/$DOMAIN/privkey.pem;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers off;

    location / {
        proxy_pass http://icc-crm-app:3000;
        proxy_http_version 1.1;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection \$icc_crm_connection_upgrade;
        proxy_read_timeout 300;
        proxy_send_timeout 300;
    }
}
EOF
  docker exec zeus-dashboard-nginx nginx -t
  docker exec zeus-dashboard-nginx nginx -s reload || docker restart zeus-dashboard-nginx
else
  log "Certbot failed; CRM remains available over HTTP. Check DNS/Cloudflare and rerun this script."
fi

log "Install weekly certificate renewal hook"
sudo tee /etc/cron.weekly/icc-crm-certbot-renew >/dev/null <<EOF
#!/usr/bin/env bash
set -e
certbot renew --quiet
docker exec zeus-dashboard-nginx nginx -s reload >/dev/null 2>&1 || true
EOF
sudo chmod +x /etc/cron.weekly/icc-crm-certbot-renew

log "Health checks"
docker compose -f "$REMOTE_DIR/docker-compose.yml" ps
if [ -f "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" ]; then
  curl -kfsS --resolve "$DOMAIN:443:127.0.0.1" "https://$DOMAIN/health"
  curl -fsSI -H "Host: $DOMAIN" "http://127.0.0.1/health" | sed -n '1,8p'
else
  curl -fsS -H "Host: $DOMAIN" "http://127.0.0.1/health"
fi
REMOTE_SETUP

log "Public checks"
curl -fsS -I "http://$DOMAIN/health" | sed -n '1,10p' || true
curl -kfsS -I "https://$DOMAIN/health" | sed -n '1,10p' || true

log "Done: $DOMAIN deployed to $SSH_HOST via Docker"
