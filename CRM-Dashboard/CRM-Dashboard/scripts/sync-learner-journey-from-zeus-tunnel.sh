#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
SSH_USER="${SSH_USER:-quenn}"
SSH_HOST="${SSH_HOST:-13.215.57.82}"
SSH_KEY_SOURCE="${SSH_KEY_SOURCE:-$ROOT_DIR/../conf/ssh-key}"
REMOTE_APP_DIR="${REMOTE_APP_DIR:-/var/www}"
LOCAL_TUNNEL_PORT="${LOCAL_TUNNEL_PORT:-13306}"
LEARNER_DB_SOURCE="${LEARNER_JOURNEY_SOURCE:-mysql}"

if [[ ! -f "$SSH_KEY_SOURCE" ]]; then
  echo "[learner-journey-sync] SSH key not found: $SSH_KEY_SOURCE" >&2
  exit 1
fi

TMP_SSH_KEY="/tmp/$(basename "$SSH_KEY_SOURCE").learner-journey"
cp "$SSH_KEY_SOURCE" "$TMP_SSH_KEY"
chmod 600 "$TMP_SSH_KEY"

cleanup() {
  if [[ -n "${TUNNEL_PID:-}" ]] && kill -0 "$TUNNEL_PID" >/dev/null 2>&1; then
    kill "$TUNNEL_PID" >/dev/null 2>&1 || true
    wait "$TUNNEL_PID" 2>/dev/null || true
  fi
  rm -f "$TMP_SSH_KEY"
}
trap cleanup EXIT

REMOTE_ENV=$(ssh -i "$TMP_SSH_KEY" -o BatchMode=yes -o StrictHostKeyChecking=accept-new "$SSH_USER@$SSH_HOST" \
  "docker exec zeus-dashboard-app sh -lc 'cd $REMOTE_APP_DIR && grep -E \"^(DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME|DB_PASSWORD)=\" .env'" )

DB_HOST=$(printf '%s\n' "$REMOTE_ENV" | awk -F= '/^DB_HOST=/{print substr($0,index($0,$2))}')
DB_PORT=$(printf '%s\n' "$REMOTE_ENV" | awk -F= '/^DB_PORT=/{print substr($0,index($0,$2))}')
DB_DATABASE=$(printf '%s\n' "$REMOTE_ENV" | awk -F= '/^DB_DATABASE=/{print substr($0,index($0,$2))}')
DB_USERNAME=$(printf '%s\n' "$REMOTE_ENV" | awk -F= '/^DB_USERNAME=/{print substr($0,index($0,$2))}')
DB_PASSWORD=$(printf '%s\n' "$REMOTE_ENV" | awk -F= '/^DB_PASSWORD=/{print substr($0,index($0,$2))}')

if [[ -z "$DB_HOST" || -z "$DB_PORT" || -z "$DB_DATABASE" || -z "$DB_USERNAME" || -z "$DB_PASSWORD" ]]; then
  echo "[learner-journey-sync] Cannot read Zeus DB credentials from remote .env" >&2
  exit 1
fi

ssh -f -N -L "$LOCAL_TUNNEL_PORT:$DB_HOST:$DB_PORT" -i "$TMP_SSH_KEY" -o BatchMode=yes -o StrictHostKeyChecking=accept-new "$SSH_USER@$SSH_HOST"
TUNNEL_PID=$(pgrep -f "${LOCAL_TUNNEL_PORT}:${DB_HOST}:${DB_PORT}" | head -n 1 || true)

python3 - <<PY
import socket, time, sys
host='127.0.0.1'; port=$LOCAL_TUNNEL_PORT
for _ in range(50):
    s=socket.socket()
    s.settimeout(0.5)
    try:
        s.connect((host, port))
        s.close()
        sys.exit(0)
    except Exception:
        time.sleep(0.2)
    finally:
        try: s.close()
        except Exception: pass
print('Tunnel did not open in time', file=sys.stderr)
sys.exit(1)
PY

echo "[learner-journey-sync] Tunnel ready at 127.0.0.1:$LOCAL_TUNNEL_PORT"
echo "[learner-journey-sync] Running direct Zeus MySQL sync for Learner Journey..."

cd "$ROOT_DIR"
ZEUS_DB_HOST=127.0.0.1 \
ZEUS_DB_PORT="$LOCAL_TUNNEL_PORT" \
ZEUS_DB_DATABASE="$DB_DATABASE" \
ZEUS_DB_USERNAME="$DB_USERNAME" \
ZEUS_DB_PASSWORD="$DB_PASSWORD" \
LEARNER_JOURNEY_SOURCE="$LEARNER_DB_SOURCE" \
node src/scripts/syncLearnerJourney.js

echo "[learner-journey-sync] Done"
