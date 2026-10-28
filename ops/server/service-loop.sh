#!/usr/bin/env bash
#
# Fallback for servers where cron cannot be used (no cron daemon / no crontab spool): a small background loop that does
# what the two cron jobs do, once a minute: the Laravel scheduler and keep-services-alive.sh.
#
# Run it on every deploy; if the loop is already running nothing happens (it calls the other scripts and artisan fresh on
# every round, so it needs no restart to use new code). It does not survive a server reboot: the next deploy starts it again.
#
# Usage: service-loop.sh /path/to/app /path/to/php
#
set -u

APP_PATH="${1:?usage: service-loop.sh /path/to/app /path/to/php}"
PHP_BIN="${2:?usage: service-loop.sh /path/to/app /path/to/php}"

cd "${APP_PATH}" || exit 1
mkdir -p storage/run storage/logs

if [ "${3:-}" != "--daemon" ]; then
    pid_file="storage/run/service-loop.pid"

    if [ -f "${pid_file}" ] && kill -0 "$(cat "${pid_file}")" 2>/dev/null && grep -aq service-loop "/proc/$(cat "${pid_file}")/cmdline" 2>/dev/null; then
        echo "service loop already running (pid $(cat "${pid_file}"))"
        exit 0
    fi

    nohup bash "$0" "${APP_PATH}" "${PHP_BIN}" --daemon >> storage/logs/service-loop.log 2>&1 &
    echo $! > "${pid_file}"
    echo "service loop started (pid $!)"
    exit 0
fi

while true; do
    "${PHP_BIN}" artisan schedule:run >> /dev/null 2>&1
    bash "${APP_PATH}/ops/server/keep-services-alive.sh" "${APP_PATH}" "${PHP_BIN}"
    sleep 60
done
