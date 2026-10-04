#!/bin/sh
# Restore a dump into the database: docker compose -f compose.prod.yaml run --rm backup restore /backups/<file>.sql.gz
set -eu

file="${1:?usage: restore.sh /backups/<file>.sql.gz}"
echo "This will REPLACE the contents of database ${PGDATABASE} with ${file}."
printf "Type the database name to confirm: "
read -r answer
[ "$answer" = "$PGDATABASE" ] || { echo "Aborted."; exit 1; }

psql -v ON_ERROR_STOP=1 -c "DROP SCHEMA public CASCADE; CREATE SCHEMA public;" "$PGDATABASE"
gunzip -c "$file" | psql -v ON_ERROR_STOP=1 "$PGDATABASE"
echo "Restored ${file}."
