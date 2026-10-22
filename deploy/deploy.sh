#!/usr/bin/env bash
# Update the live site after pulling new code:  cd /var/www/lms && bash deploy/deploy.sh
set -euo pipefail
cd "$(dirname "$0")/.."

echo "==> Backend"
cd backend
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize
php artisan queue:restart
cd ..

echo "==> Student website"
cd student-app
npm ci
npm run build
cd ..

sudo systemctl restart lms-student
echo "Done."
