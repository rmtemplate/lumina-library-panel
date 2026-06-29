#!/usr/bin/env bash
#
# Backs up the MySQL database (always) and the storage_public volume
# (optional, --with-storage) to ./backups/, gzip-compressed and
# timestamped. Prunes backups older than BACKUP_RETENTION_DAYS (default 14).
#
# Usage:
#   ./scripts/backup.sh                  # database only
#   ./scripts/backup.sh --with-storage   # database + uploaded files
#   ./scripts/backup.sh --quiet          # minimal output (used by update.sh)
#
# Restore:
#   gunzip < backups/db_<timestamp>.sql.gz | docker compose exec -T mysql \
#     mysql -uroot -p"$DB_ROOT_PASSWORD" "$DB_DATABASE"
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

WITH_STORAGE=false
QUIET=false
for arg in "$@"; do
    case "$arg" in
        --with-storage) WITH_STORAGE=true ;;
        --quiet) QUIET=true ;;
    esac
done

log() { [ "$QUIET" = true ] || echo "$@"; }

if [ ! -f .env ]; then
    echo "ERROR: .env not found."
    exit 1
fi

# shellcheck disable=SC1091
set -a; source .env; set +a

BACKUP_DIR="./backups"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-14}"
PROJECT_NAME="lumina-library"

mkdir -p "$BACKUP_DIR"

# ── Database ────────────────────────────────────────────────────────────
log "==> Dumping database '${DB_DATABASE}'..."
DB_BACKUP_FILE="${BACKUP_DIR}/db_${TIMESTAMP}.sql.gz"

docker compose exec -T mysql \
    mysqldump -uroot -p"${DB_ROOT_PASSWORD}" \
        --single-transaction \
        --routines \
        --triggers \
        "${DB_DATABASE}" \
    | gzip > "$DB_BACKUP_FILE"

log "    -> ${DB_BACKUP_FILE} ($(du -h "$DB_BACKUP_FILE" | cut -f1))"

# ── Storage (optional) ───────────────────────────────────────────────────
if [ "$WITH_STORAGE" = true ]; then
    log "==> Archiving storage_public volume..."
    STORAGE_BACKUP_FILE="${BACKUP_DIR}/storage_${TIMESTAMP}.tar.gz"
    VOLUME_NAME="${PROJECT_NAME}_storage_public"

    docker run --rm \
        -v "${VOLUME_NAME}:/data:ro" \
        -v "$(pwd)/${BACKUP_DIR}:/backup" \
        alpine:3 \
        tar czf "/backup/storage_${TIMESTAMP}.tar.gz" -C /data .

    log "    -> ${STORAGE_BACKUP_FILE} ($(du -h "$STORAGE_BACKUP_FILE" | cut -f1))"
fi

# ── Retention ─────────────────────────────────────────────────────────────
log "==> Pruning backups older than ${RETENTION_DAYS} days..."
find "$BACKUP_DIR" -name '*.sql.gz' -mtime "+${RETENTION_DAYS}" -delete
find "$BACKUP_DIR" -name '*.tar.gz' -mtime "+${RETENTION_DAYS}" -delete

log "==> Backup complete."
