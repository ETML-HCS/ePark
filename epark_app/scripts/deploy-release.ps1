param(
    [string]$Release = (Get-Date -Format 'yyyyMMdd-HHmmss'),
    [string]$IdentityFile = "$env:USERPROFILE\.ssh\athys_hosting_ed25519",
    [switch]$PrepareOnly,
    [switch]$StageOnly
)
$ErrorActionPreference = 'Stop'
if ($Release -notmatch '^\d{8}-\d{6}$') { throw 'Invalid release identifier.' }
$project = Split-Path -Parent $PSScriptRoot
$remoteBase = '/home/u871035213/domains/athys.ch'
$remoteRelease = "$remoteBase/epark-releases/$Release"
$sshOpts = @('-o','BatchMode=yes','-o','ConnectTimeout=30','-o','ServerAliveInterval=15','-o','ServerAliveCountMax=4','-i',$IdentityFile)
$sshArgs = $sshOpts + @('athys.ch')
$scpArgs = @('-q','-P','65002') + $sshOpts
function Remote([string]$Command) {
    & ssh @sshArgs $Command
    if ($LASTEXITCODE -ne 0) { throw 'Remote release command failed.' }
}
Push-Location $project
try {
    if (!(Test-Path public/build/manifest.json)) { throw 'Build the assets before packaging.' }
    New-Item -ItemType Directory -Force output/release | Out-Null
    $archiveName = "epark-$Release.tar.gz"
    $archive = Join-Path $project "output/release/$archiveName"
    & tar -czf $archive '--exclude=bootstrap/cache/*.php' '--exclude=public/hot' '--exclude=public/storage' app bootstrap config database/migrations lang resources routes public artisan composer.json composer.lock
    if ($LASTEXITCODE -ne 0) { throw 'Packaging failed.' }
    if ($PrepareOnly) { Write-Output $archive; return }
    Write-Output "[1/3] Preparing remote release directory $Release..."
    $envState = & ssh @sshArgs "set -eu; test ! -e '$remoteRelease'; mkdir -p '$remoteRelease' '$remoteBase/epark-shared/storage/framework/cache/data' '$remoteBase/epark-shared/storage/framework/sessions' '$remoteBase/epark-shared/storage/framework/views' '$remoteBase/epark-shared/storage/logs' '$remoteBase/epark-shared/storage/app/private' '$remoteBase/epark-shared/storage/app/public'; chmod 700 '$remoteBase/epark-shared'; if test -f '$remoteBase/epark-shared/.env'; then echo ENV_OK; else echo ENV_MISSING; fi"
    if ($LASTEXITCODE -ne 0) { throw 'Remote directory preparation failed.' }
    Write-Output "[2/3] Uploading archive and release helpers..."
    & scp @scpArgs $archive 'scripts/release-ops.php' 'scripts/hostinger-index.php' 'scripts/install-scheduler.sh' "u871035213@145.223.104.237:$remoteRelease/"
    if ($LASTEXITCODE -ne 0) { throw 'Release upload failed.' }
    if (($envState -join "`n") -notmatch 'ENV_OK') {
        & scp @scpArgs '.env.production' "u871035213@145.223.104.237:$remoteBase/epark-shared/.env"
        if ($LASTEXITCODE -ne 0) { throw 'Environment upload failed.' }
    }
    Write-Output "[3/3] Installing dependencies, backing up DB, migrating and switching release..."
    $stageCmd = "set -eu; cd '$remoteRelease'; tar -xzf '$archiveName'; rm -f '$archiveName'; ln -s '$remoteBase/epark-shared/.env' .env; ln -s '$remoteBase/epark-shared/storage' storage; chmod 600 '$remoteBase/epark-shared/.env'; composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader; php release-ops.php preflight; php release-ops.php backup"
    if ($StageOnly) {
        Remote $stageCmd
        Write-Output "Release staged and database backed up: $remoteRelease"
        return
    }
    $publishCmd = "$stageCmd; php artisan migrate --force; php artisan config:cache; php artisan view:cache; cd '$remoteBase'; cp -a '$remoteRelease/public' 'public_html/.epark-$Release'; sed 's/__EPARK_RELEASE__/$Release/g' '$remoteRelease/hostinger-index.php' > 'public_html/.epark-$Release/index.php'; if test -e public_html/epark; then mv public_html/epark 'epark-shared/backups/web-$Release'; fi; ln -sfn '$remoteRelease' epark-current; mv 'public_html/.epark-$Release' public_html/epark; printf '%s\n' '$Release' > epark-shared/active-release; sh '$remoteRelease/install-scheduler.sh'"
    Remote $publishCmd
    Write-Output "Published release $Release at https://epark.athys.ch/; verify the public site."
} finally { Pop-Location }
