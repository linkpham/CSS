#!/usr/bin/env sh
set -eu

INTERVAL_MS="${SYNC_INTERVAL_MS:-300000}"
INTERVAL_SEC=$((INTERVAL_MS / 1000))
if [ "$INTERVAL_SEC" -le 0 ]; then
  INTERVAL_SEC=300
fi

echo "[sync-loop] starting with interval ${INTERVAL_SEC}s"

while true; do
  if node src/scripts/sync.js; then
    echo "[sync-loop] sync succeeded"
  else
    echo "[sync-loop] sync failed; retrying after ${INTERVAL_SEC}s" >&2
  fi
  sleep "$INTERVAL_SEC"
done
