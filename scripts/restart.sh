#!/usr/bin/env bash
#
# Restart one service, or the whole stack.
#
# Usage:
#   ./scripts/restart.sh              # restart every service
#   ./scripts/restart.sh queue        # restart only the queue worker
#   ./scripts/restart.sh reverb       # restart only Reverb
#
# A plain restart (not a rebuild) — use scripts/update.sh when the image
# itself needs to change.
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

SERVICE="${1:-}"

if [ -n "$SERVICE" ]; then
    echo "==> Restarting service: ${SERVICE}"
    docker compose restart "$SERVICE"
else
    echo "==> Restarting the entire stack"
    docker compose restart
fi

echo "==> Current status:"
docker compose ps
