#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
export HOST="${HOST:-0.0.0.0}"
export PORT="${PORT:-3000}"
echo "Starting CRM Dashboard on http://127.0.0.1:${PORT}"
echo "If browser is on Windows/host, also try WSL IP printed by: hostname -I"
node src/app.js
