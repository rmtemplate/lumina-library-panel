#!/usr/bin/env bash
#
# Shared entrypoint for every PHP container (app, queue, scheduler, reverb).
# Behavior branches on $CONTAINER_ROLE, set per-service in docker-compose.yml
# — this keeps a single image/Dockerfile for all four roles instead of
# duplicating Dockerfiles.
set -euo pipefail

log() { echo "[entrypoint] $*"; }

wait_for_tcp() {
    local host="$1" port="$2" label="$3" tries=0

    log "Waiting for ${label} (${host}:${port})..."
    until nc -z "$host" "$port" >/dev/null 2>&1; do
        tries=$((tries + 1))
        if [ "$tries" -ge 60 ]; then
            log "ERROR: ${label} (${host}:${port}) did not become reachable in time."
            exit 1
        fi
        sleep 2
    done
    log "${label} is reachable."
}

# storage/ and bootstrap/cache are writable named volumes on some hosts and
# baked-image directories on others (see docker-compose.yml) — re-assert
# ownership on every boot so a freshly-created empty volume doesn't end up
# owned by root.
fix_permissions() {
    mkdir -p storage/framework/{cache,sessions,testing,views} storage/logs bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
    chmod -R 775 storage bootstrap/cache 2>/dev/null || true
}

cd /var/www/html

ROLE="${CONTAINER_ROLE:-app}"
log "Starting container with role: ${ROLE}"

fix_permissions

if [ -n "${DB_HOST:-}" ]; then
    wait_for_tcp "${DB_HOST}" "${DB_PORT:-3306}" "MySQL"
fi

if [ -n "${REDIS_HOST:-}" ]; then
    wait_for_tcp "${REDIS_HOST}" "${REDIS_PORT:-6379}" "Redis"
fi

case "$ROLE" in
    app)
        # Only the app (web) container runs migrations/cache warmup — running
        # this from every container would race on the same `migrations`
        # table and cache files. Set SKIP_MIGRATIONS=true to disable (e.g.
        # if you run migrations manually as a separate deploy step).
        if [ "${SKIP_MIGRATIONS:-false}" != "true" ]; then
            log "Running database migrations..."
            php artisan migrate --force
        fi

        log "Linking public storage..."
        php artisan storage:link || true

        log "Caching config/routes/views/events..."
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        php artisan event:cache

        log "Boot complete — starting php-fpm."
        exec php-fpm
        ;;

    queue)
        exec supervisord -n -c /etc/supervisor/queue.conf
        ;;

    reverb)
        exec supervisord -n -c /etc/supervisor/reverb.conf
        ;;

    scheduler)
        exec supervisord -n -c /etc/supervisor/scheduler.conf
        ;;

    *)
        log "Unknown CONTAINER_ROLE '${ROLE}', executing passed command as-is."
        exec "$@"
        ;;
esac
