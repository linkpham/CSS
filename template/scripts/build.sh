#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
APP_DIR="${APP_DIR:-$ROOT_DIR/CRM-Dashboard}"

echo "[build] root: $ROOT_DIR"
echo "[build] app:  $APP_DIR"

if [ ! -d "$APP_DIR" ]; then
  echo "[build] skip: app directory not found. Set APP_DIR or create CRM-Dashboard/." >&2
  exit 0
fi

if [ -f "$APP_DIR/package-lock.json" ]; then
  (cd "$APP_DIR" && npm ci)
elif [ -f "$APP_DIR/package.json" ]; then
  (cd "$APP_DIR" && npm install)
else
  echo "[build] skip: no package.json found in $APP_DIR" >&2
  exit 0
fi

if npm --prefix "$APP_DIR" run | grep -qE '^  build$|^    build$'; then
  npm --prefix "$APP_DIR" run build
else
  echo "[build] no npm build script; running syntax/smoke placeholder only"
fi

echo "[build] done"
