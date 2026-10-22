#!/usr/bin/env bash
# Nightly backup of the database and the uploaded files (PDFs, images).
# Cron (as the deploy user):  30 2 * * * bash /var/www/lms/deploy/backup.sh >> /var/log/lms-backup.log 2>&1
# Keep the backups OFF this server too (copy /var/backups/lms to another machine or cloud storage).
set -euo pipefail

DEST=/var/backups/lms
STAMP=$(date +%Y-%m-%d)
mkdir -p "$DEST"

# Reads DB settings from backend/.env
set -a; source /var/www/lms/backend/.env; set +a
PGPASSWORD="$DB_PASSWORD" pg_dump -h "$DB_HOST" -U "$DB_USERNAME" "$DB_DATABASE" | gzip > "$DEST/db-$STAMP.sql.gz"
tar -czf "$DEST/files-$STAMP.tar.gz" -C /var/www/lms/backend/storage/app .

# Keep 14 days
find "$DEST" -type f -mtime +14 -delete
echo "$(date) backup ok"
