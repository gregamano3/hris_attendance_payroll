#!/bin/sh
# Daily compressed PostgreSQL dumps with retention.
# Env: PGHOST PGUSER PGPASSWORD PGDATABASE BACKUP_RETENTION_DAYS BACKUP_AT (HH:MM, default 02:00)
set -eu

BACKUP_DIR=/backups
RETENTION="${BACKUP_RETENTION_DAYS:-14}"
AT="${BACKUP_AT:-02:00}"

backup() {
    file="$BACKUP_DIR/${PGDATABASE}-$(date +%Y%m%d-%H%M%S).sql.gz"
    echo "[backup] $(date -Iseconds) dumping ${PGDATABASE} to ${file}"
    pg_dump --no-owner --no-privileges "$PGDATABASE" | gzip -9 > "${file}.partial"
    mv "${file}.partial" "$file"
    find "$BACKUP_DIR" -name "${PGDATABASE}-*.sql.gz" -mtime "+${RETENTION}" -print -delete
    echo "[backup] done ($(du -h "$file" | cut -f1))"
}

if [ "${1:-}" = "once" ]; then
    backup
    exit 0
fi

while true; do
    if [ "$(date +%H:%M)" = "$AT" ]; then
        backup || echo "[backup] FAILED" >&2
        sleep 60
    fi
    sleep 30
done
