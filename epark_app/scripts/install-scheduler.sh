#!/bin/sh
set -eu
base=/home/u871035213/domains/athys.ch
task="* * * * * /opt/alt/php84/usr/bin/php $base/epark-current/artisan schedule:run >> $base/epark-shared/storage/logs/scheduler.log 2>&1"
if ! command -v crontab >/dev/null 2>&1; then
    printf '%s\n' "$task" > "$base/epark-shared/scheduler-command.txt"
    printf 'EPARK_SCHEDULER_REQUIRES_HOSTINGER_PANEL: crontab is unavailable; command saved in private shared storage.\n'
    exit 0
fi
cronFile=$(mktemp)
trap 'rm -f "$cronFile"' EXIT
crontab -l > "$cronFile" 2>/dev/null || true
if ! grep -Fq "$base/epark-current/artisan schedule:run" "$cronFile"; then
    printf '\n%s\n' "$task" >> "$cronFile"
    crontab "$cronFile"
fi
printf 'EPARK_SCHEDULER_INSTALLED\n'
