param([switch]$SkipUpload, [switch]$AllowScp, [switch]$UnzipRemote, [switch]$IncludeVendor)
$ErrorActionPreference = 'Stop'
Push-Location $PSScriptRoot
try {
    & npm run build
    if ($LASTEXITCODE -ne 0) { throw 'Asset build failed. Nothing was uploaded.' }
    & php artisan test --compact
    if ($LASTEXITCODE -ne 0) { throw 'Tests failed. Nothing was uploaded.' }
    & "$PSScriptRoot/scripts/deploy-release.ps1" -PrepareOnly:$SkipUpload
} finally { Pop-Location }
