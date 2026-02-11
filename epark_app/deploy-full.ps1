param(
    [string]$HostUser = "u871035213",
    [string]$HostName = "145.223.104.237",
    [int]$Port = 65002,
    [string]$RemotePath = "/home/u871035213/domains/athys.ch/public_html/epark/",
    [string]$EnvFile = ".env.production",
    [switch]$AllowScp,
    [switch]$UnzipRemote,
    [switch]$PublicRootAtProjectRoot
)

$ErrorActionPreference = "Stop"

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$deployRoot = Join-Path $projectRoot "_deploy"
$publicRoot = Join-Path $projectRoot "public"
$zipName = "epark_deploy_structured_$((Get-Date).ToString('yyyyMMdd-HHmm')).zip"
$zipPath = Join-Path $projectRoot $zipName

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
    "resources",
    "routes",
    "storage",
    "vendor",
    "artisan",
    "composer.json",
    "composer.lock"
)

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
        $remoteCmd = @(
            "cd $RemotePath",
            "unzip -o $zipName",
            'sed -i "s#../vendor/autoload.php#vendor/autoload.php#g" index.php',
            'sed -i "s#../bootstrap/app.php#bootstrap/app.php#g" index.php'
        )
        if ($PublicRootAtProjectRoot) {
            $remoteCmd += "cp -R public/* ."
            $remoteCmd += "cp public/.htaccess .htaccess"
        }
        & ssh -p $Port "${HostUser}@${HostName}" ($remoteCmd -join " && ")
    }
}

Write-Host "Done." -ForegroundColor Green
