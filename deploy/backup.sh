#!/usr/bin/env bash
set -euo pipefail
: "${BACKUP_REMOTE:?Configure off-server rclone destination}"
: "${BACKUP_AGE_RECIPIENT:?Configure age public recipient}"
: "${MYSQL_OPTION_FILE:?Configure restricted defaults-extra-file}"
: "${DB_DATABASE:?Configure application database}"
: "${STUDIO_SHARED:=/srv/studio/shared}"
umask 077
backup_work=$(mktemp -d)
trap 'rm -rf -- "$backup_work"' EXIT
mysqldump --defaults-extra-file="$MYSQL_OPTION_FILE" --single-transaction --routines --triggers --no-tablespaces "$DB_DATABASE" > "$backup_work/database.sql"
tar -C "$STUDIO_SHARED" -czf "$backup_work/files-and-key.tar.gz" storage/app .env
tar -C "$backup_work" -cf - database.sql files-and-key.tar.gz | age -r "$BACKUP_AGE_RECIPIENT" -o "$backup_work/studio-backup.tar.age"
rclone copyto "$backup_work/studio-backup.tar.age" "$BACKUP_REMOTE/$(date -u +%Y-%m-%dT%H-%M-%SZ).tar.age"
# Operator configures retention, backup-age alerting and restore drills.
