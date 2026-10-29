$ErrorActionPreference = "Stop"

composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link 2>$null
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

Write-Host "Deploiement termine."