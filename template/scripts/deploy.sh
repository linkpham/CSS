#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
APP_DIR="${APP_DIR:-$ROOT_DIR/CRM-Dashboard}"
DISCORD_CNF="${DISCORD_CNF:-$ROOT_DIR/config/discord.cnf}"

echo "[deploy] root: $ROOT_DIR"
echo "[deploy] app:  $APP_DIR"

if [ ! -f "$ROOT_DIR/REQUESTS.md" ]; then
  echo "[deploy] missing REQUESTS.md; stop before deploy" >&2
  exit 1
fi

if [ -d "$ROOT_DIR/docs/specs" ]; then
  echo "[deploy] specs present; ensure current run trace records spec review"
fi

"$ROOT_DIR/scripts/build.sh"

if [ ! -d "$APP_DIR" ]; then
  echo "[deploy] skip: app directory not found. Create CRM-Dashboard/ before deploy." >&2
  exit 0
fi

echo "[deploy] TODO: add project-specific deploy target here"
echo "[deploy] expected checks: health endpoint, auth flow, dashboard render, role guard"

if [ -f "$DISCORD_CNF" ]; then
  echo "[deploy] Discord config found at $DISCORD_CNF; send deployment report in project-specific implementation"
else
  echo "[deploy] Discord config not found; skip notification"
fi

echo "[deploy] done"
