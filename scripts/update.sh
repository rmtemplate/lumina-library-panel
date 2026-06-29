#!/usr/bin/env bash
#
# Deploy an update to an already-running stack.
#
# Usage: ./scripts/update.sh [git-ref]
#
# - Pulls the given git ref (defaults to the current branch's upstream).
# - Rebuilds images.
# - Recreates only the containers whose image actually changed (Docker
#   Compose skips containers with no diff).
# - Runs a database backup FIRST, so `scripts/restart.sh rollback` (see
#   docs/deployment.md "Rollback") always has a pre-update snapshot.
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

COMPOSE="docker compose"
GIT_REF="${1:-}"

echo "==> Lumina Library — update deployment"

if [ ! -f .env ]; then
    echo "ERROR: .env not found. Run scripts/deploy.sh first."
    exit 1
fi

echo "==> Backing up the database before updating..."
./scripts/backup.sh --quiet || {
    echo "WARNING: pre-update backup failed — continuing anyway. Investigate scripts/backup.sh."
}

echo "==> Recording current image IDs (for rollback reference)..."
docker compose images app queue scheduler reverb nginx > .last-deploy-images.txt 2>/dev/null || true

if [ -n "$GIT_REF" ]; then
    echo "==> Checking out ${GIT_REF}..."
    git fetch --all --tags
    git checkout "$GIT_REF"
else
    echo "==> Pulling latest changes on the current branch..."
    git pull --ff-only
fi

echo "==> Rebuilding images..."
$COMPOSE build --pull

echo "==> Recreating changed containers (zero-downtime for unchanged services)..."
$COMPOSE up -d --remove-orphans

echo "==> Waiting for the app container to become healthy..."
tries=0
until [ "$(docker inspect -f '{{.State.Health.Status}}' lumina-app 2>/dev/null)" = "healthy" ]; do
    tries=$((tries + 1))
    if [ "$tries" -ge 60 ]; then
        echo "ERROR: app container did not become healthy after the update."
        echo "       Check logs: docker compose logs app"
        echo "       Or roll back: see docs/deployment.md 'Rollback'"
        exit 1
    fi
    sleep 3
done

echo "==> Cleaning up dangling images..."
docker image prune -f >/dev/null 2>&1 || true

echo ""
echo "==> Update complete."
$COMPOSE ps
