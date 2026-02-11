<#
.SYNOPSIS
    Deploy Laravel app to Hostinger production server

.DESCRIPTION
    Builds Vite assets, creates deployment zip, uploads via scp, and configures on remote server.
    IMPORTANT: Include vendor/ directory to avoid composer issues on shared hosting.

.PARAMETER IncludeVendor
    Include vendor/ directory in deployment (RECOMMENDED - avoids composer path issues)

.PARAMETER UnzipRemote
    Automatically extract and configure on server after upload

.PARAMETER AllowScp
    Use scp instead of rsync (required if rsync not available)

.EXAMPLE
    # Full production deployment with vendor
    .\deploy-full.ps1 -AllowScp -UnzipRemote -IncludeVendor

.NOTES
    - Document root MUST serve /epark/ not /epark/public/
    - Script copies public/index.php to root and adjusts paths automatically
    - Regenerates Composer autoload to fix Windows->Linux path issues
    - Copies build/ assets to project root for proper CSS/JS loading
#>

param(
    [string]$HostUser = "u871035213",
    [string]$HostName = "145.223.104.237",
    [int]$Port = 65002,
    [string]$RemotePath = "/home/u871035213/domains/athys.ch/public_html/epark/",
    [string]$EnvFile = ".env.production",
    [switch]$AllowScp,
    [switch]$UnzipRemote,
    [switch]$PublicRootAtProjectRoot,
    [switch]$SkipUpload,
    [switch]$IncludeVendor
)

$ErrorActionPreference = "Stop"

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$deployRoot = Join-Path $projectRoot "_deploy"
$publicRoot = Join-Path $projectRoot "public"
$zipName = "epark_deploy_structured_$((Get-Date).ToString('yyyyMMdd-HHmm')).zip"
$zipPath = Join-Path $projectRoot $zipName

# Warn if vendor is excluded (may cause issues on shared hosting)
if (-not $IncludeVendor -and -not $SkipUpload) {
    Write-Host "WARNING: Deploying without vendor/ directory." -ForegroundColor Yellow
    Write-Host "  You will need to run 'composer install' on the server manually." -ForegroundColor Yellow
    Write-Host "  Recommended: Use -IncludeVendor flag to avoid path issues." -ForegroundColor Yellow
    Write-Host ""
}

function Clear-DirContents([string]$path) {
    if (Test-Path $path) {
        Get-ChildItem -Path $path -Force | Where-Object { $_.Name -ne ".gitignore" } | Remove-Item -Recurse -Force
    }
}

Write-Host "Building assets..." -ForegroundColor Cyan
Set-Location $projectRoot
npm run build

Write-Host "Preparing deploy folder..." -ForegroundColor Cyan
if (!(Test-Path $deployRoot)) {
    New-Item -ItemType Directory -Path $deployRoot | Out-Null
}

$copyItems = @(
    "app",
    "bootstrap",
    "config",
    "database",
    "lang",
    "resources",
    "routes",
    "storage",
    "artisan",
    "composer.json",
    "composer.lock"
)

if ($IncludeVendor) {
    $copyItems += "vendor"
}

foreach ($item in $copyItems) {
    $src = Join-Path $projectRoot $item
    if (Test-Path $src) {
        Copy-Item $src -Destination $deployRoot -Recurse -Force
    }
}

Copy-Item $publicRoot -Destination $deployRoot -Recurse -Force

# Optional: keep a root-level public for servers pointing to /epark instead of /epark/public
if ($PublicRootAtProjectRoot) {
    Copy-Item (Join-Path $publicRoot "*") -Destination $deployRoot -Recurse -Force
}

# Remove environment-specific runtime data (avoid Windows paths and local artifacts being deployed)
$storagePaths = @(
    "storage\framework\cache",
    "storage\framework\sessions",
    "storage\framework\views",
    "storage\framework\testing",
    "storage\logs",
    "storage\app\private",
    "storage\app\public"
)

foreach ($relativePath in $storagePaths) {
    $fullPath = Join-Path $deployRoot $relativePath
    Clear-DirContents $fullPath
    if (!(Test-Path $fullPath)) {
        New-Item -ItemType Directory -Path $fullPath | Out-Null
    }
}

$indexPath = Join-Path $deployRoot "index.php"
if (Test-Path $indexPath) {
    $content = Get-Content $indexPath -Raw
    $content = $content -replace "require __DIR__\s*\.\s*'/\.\./vendor/autoload\.php';", "require __DIR__.'/vendor/autoload.php';"
    $content = $content -replace "require_once __DIR__\s*\.\s*'/\.\./bootstrap/app\.php';", "require_once __DIR__.'/bootstrap/app.php';"
    Set-Content -Path $indexPath -Value $content -Encoding UTF8
}

$envSource = Join-Path $projectRoot $EnvFile
if (Test-Path $envSource) {
    Copy-Item $envSource -Destination (Join-Path $deployRoot ".env") -Force
}

Write-Host "Creating deployment zip..." -ForegroundColor Cyan
if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}
Compress-Archive -Path (Join-Path $deployRoot "*") -DestinationPath $zipPath

if ($SkipUpload) {
    Write-Host "Zip created at: $zipPath" -ForegroundColor Green
    return
}

Write-Host "Uploading project to server..." -ForegroundColor Cyan
$rsync = Get-Command rsync -ErrorAction SilentlyContinue
if (-not $rsync -and -not $AllowScp) {
    throw "rsync is required for fast deploys. Install rsync or rerun with -AllowScp."
}

$exclude = @(
    "storage/logs",
    "storage/framework/cache",
    "storage/framework/sessions",
    "storage/framework/views",
    ".env",
    ".env.*",
    "node_modules",
    "tests",
    "*.log"
)

$excludeArgs = @()
foreach ($pattern in $exclude) {
    $excludeArgs += "--exclude=$pattern"
}

if ($rsync) {
    & rsync -avz --delete --delete-excluded -e "ssh -p $Port" @excludeArgs "$deployRoot/" "${HostUser}@${HostName}:$RemotePath"
} else {
    Write-Host "rsync not found, using scp (no delete, slower)." -ForegroundColor Yellow
    & scp -P $Port $zipPath "${HostUser}@${HostName}:$RemotePath"

    if ($UnzipRemote) {
        Write-Host "Extracting and configuring on remote server..." -ForegroundColor Cyan
        $remoteCmd = @(
            "cd $RemotePath",
            "unzip -o $zipName"
        )
        
        # Fix index.php paths for root deployment (docroot = epark/ not epark/public/)
        $remoteCmd += "cp public/index.php index.php"
        $remoteCmd += 'sed -i "s#__DIR__\.\x27/../vendor/autoload.php\x27#__DIR__.\x27/vendor/autoload.php\x27#g" index.php'
        $remoteCmd += 'sed -i "s#__DIR__\.\x27/../bootstrap/app.php\x27#__DIR__.\x27/bootstrap/app.php\x27#g" index.php'
        
        # Copy build assets to epark root (document root serves epark/ not epark/public/)
        $remoteCmd += "rm -rf build"
        $remoteCmd += "cp -r public/build ."
        
        if ($PublicRootAtProjectRoot) {
            $remoteCmd += "cp -R public/* ."
            $remoteCmd += "cp public/.htaccess .htaccess"
        }
        
        # CRITICAL: Regenerate Composer autoload to fix Windows paths (X:/) -> Linux paths
        $remoteCmd += "composer dump-autoload --optimize --no-interaction"
        
        # Clear package discovery cache (prevents 'Class not found' errors for service providers)
        $remoteCmd += "rm -f bootstrap/cache/packages.php bootstrap/cache/services.php"
        
        # Set permissions (avoid chown to prevent invalid group errors on some hosts)
        $remoteCmd += "chmod -R ug+rwX storage bootstrap/cache"

        # Clear all Laravel caches before migration
        $remoteCmd += "php artisan optimize:clear"
        $remoteCmd += "php artisan view:clear"
        $remoteCmd += "php artisan config:clear"
        
        # Regenerate package discovery
        $remoteCmd += "php artisan package:discover --ansi"
        
        # Run migrations (force in production)
        $remoteCmd += "php artisan migrate --force"

        # Regenerate optimized config cache
        $remoteCmd += "php artisan config:cache"
        
        & ssh -p $Port "${HostUser}@${HostName}" ($remoteCmd -join " && ")
        
        Write-Host ""
        Write-Host "Deployment completed successfully!" -ForegroundColor Green
        Write-Host "  - Vite assets built and copied to /build/" -ForegroundColor Cyan
        Write-Host "  - index.php paths adjusted for root deployment" -ForegroundColor Cyan
        Write-Host "  - Composer autoload regenerated (Linux paths)" -ForegroundColor Cyan
        Write-Host "  - Laravel caches cleared and regenerated" -ForegroundColor Cyan
        Write-Host "  - Migrations executed" -ForegroundColor Cyan
        Write-Host ""
        Write-Host "Site URL: https://epark.athys.ch" -ForegroundColor Magenta
    }
}

if ($SkipUpload) {
    Write-Host ""
    Write-Host "Zip created (upload skipped): $zipPath" -ForegroundColor Green
} elseif (-not $UnzipRemote) {
    Write-Host ""
    Write-Host "Upload completed. Remember to extract and configure on server manually." -ForegroundColor Yellow
}
