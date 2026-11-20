$phpunitUrl = 'https://phar.phpunit.de/phpunit.phar'
$destDir = Join-Path $PSScriptRoot '..\vendor\bin' | Resolve-Path -Relative
$destDir = (Join-Path $PSScriptRoot '..\vendor\bin')
if (-not (Test-Path $destDir)) { New-Item -ItemType Directory -Path $destDir -Force | Out-Null }
$pharPath = Join-Path $destDir 'phpunit.phar'
Write-Host "Downloading phpunit.phar from $phpunitUrl to $pharPath"
try {
    Invoke-WebRequest -Uri $phpunitUrl -OutFile $pharPath -UseBasicParsing -ErrorAction Stop
} catch {
    Write-Error "Download failed: $_"
    exit 1
}
# Create PHP wrapper
$wrapperPath = Join-Path $destDir 'phpunit'
$wrapperLines = @(
    '#!/usr/bin/env php',
    '<?php',
    '// Wrapper to execute the phpunit phar downloaded alongside this file',
    '$phar = __DIR__ . DIRECTORY_SEPARATOR . '\'' + 'phpunit.phar' + '\'' ,
    'if (!file_exists($phar)) {',
    '    fwrite(STDERR, "phpunit.phar not found in " . __DIR__ . "\\n");',
    '    exit(1);',
    '}',
    'require $phar;',
)
Set-Content -Path $wrapperPath -Value $wrapperLines -Encoding UTF8
Write-Host "Created wrapper $wrapperPath"
# Ensure file is writable/executable on Unix-like systems (no-op on Windows)
try { chmod $wrapperPath 0755 } catch {}
Write-Host "phpunit.phar installed. You can run 'vendor\\bin\\phpunit' or 'php vendor\\bin\\phpunit.phar'"
