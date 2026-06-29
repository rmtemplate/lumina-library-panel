#!/usr/bin/env bash
#
# First-time production deployment.
#
# Usage: ./scripts/deploy.sh
#
# Run from the project root on the VPS. Builds every image, starts the
# full stack, and waits for the app container to report healthy.
# Migrations, storage:link, and config/route/view/event caching all run
# automatically inside the `app` container's entrypoint — nothing extra
# to run by hand.
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

COMPOSE="docker compose"

echo "==> Lumina Library — first-time deployment"

if [ ! -f .env ]; then
    echo "ERROR: .env not found."
    echo "       cp .env.production.example .env"
    echo "       then fill in every blank value (APP_KEY, DB_*, REDIS_PASSWORD, REVERB_*, MIDTRANS_*, ...)"
    exit 1
fi

# shellcheck disable=SC1091
set -a; source .env; set +a

if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: APP_KEY is empty in .env."
    echo "       Generate one with: docker run --rm -v \"\$(pwd)\":/app -w /app composer:2 sh -c \\"
    echo "         \"composer install --no-dev --no-scripts -q && php -r \\\"echo 'base64:'.base64_encode(random_bytes(32));\\\"\""
    echo "       Paste the output into APP_KEY= in .env, then re-run this script."
    exit 1
fi

for required in DB_PASSWORD DB_ROOT_PASSWORD REDIS_PASSWORD REVERB_APP_ID REVERB_APP_KEY REVERB_APP_SECRET; do
    if [ -z "${!required:-}" ]; then
        echo "ERROR: ${required} is empty in .env — fill it in before deploying."
        exit 1
    fi
done

echo "==> Building images (this can take a few minutes the first time)..."
$COMPOSE build --pull

echo "==> Starting the stack..."
$COMPOSE up -d

echo "==> Waiting for the app container to become healthy..."
tries=0
until [ "$(docker inspect -f '{{.State.Health.Status}}' lumina-app 2>/dev/null)" = "healthy" ]; do
    tries=$((tries + 1))
    if [ "$tries" -ge 60 ]; then
        echo "ERROR: app container did not become healthy in time. Check logs:"
        echo "       docker compose logs app"
        exit 1
    fi
    sleep 3
done

echo ""
echo "==> Deployment complete. Current status:"
$COMPOSE ps

echo ""
echo "Next steps:"
echo "  - Point your reverse proxy (Nginx Proxy Manager / Cloudflare) at this host's port \${NGINX_HTTP_PORT:-80}."
echo "  - Tail logs anytime with: docker compose logs -f"
echo "  - See docs/deployment.md for SSL setup, backups, and troubleshooting."
