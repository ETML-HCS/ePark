param(
    [string]$HostUser = "u871035213",
    [string]$HostName = "145.223.104.237",
    [int]$Port = 65002,
    [string]$RemotePath = "/home/u871035213/domains/athys.ch/public_html/epark/public/build/"
)

$ErrorActionPreference = "Stop"

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$buildPath = Join-Path $projectRoot "public\build\"

Write-Host "Building assets..." -ForegroundColor Cyan
Set-Location $projectRoot
npm run build

Write-Host "Uploading build to server..." -ForegroundColor Cyan
$rsync = Get-Command rsync -ErrorAction SilentlyContinue
if ($rsync) {
    & rsync -avz --delete -e "ssh -p $Port" "$buildPath" "${HostUser}@${HostName}:$RemotePath"
} else {
    Write-Host "rsync not found, using scp (no delete)." -ForegroundColor Yellow
    & scp -P $Port -r "$buildPath*" "${HostUser}@${HostName}:$RemotePath"
}

Write-Host "Done." -ForegroundColor Green
