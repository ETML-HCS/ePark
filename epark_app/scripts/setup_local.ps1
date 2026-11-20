# Script d'initialisation locale pour ePark
Write-Host "Install composer packages"
composer install

Write-Host "Install node packages"
npm install

Write-Host "Generate APP key"
php artisan key:generate

Write-Host "Create sqlite database file if missing"
if (-not (Test-Path "database\\database.sqlite")) { New-Item -ItemType File -Path "database\\database.sqlite" | Out-Null }

Write-Host "Run migrations and seeds"
php artisan migrate --seed

Write-Host "Build frontend"
npm run build

Write-Host "Done. You can run 'php artisan serve' and 'npm run dev' as needed."