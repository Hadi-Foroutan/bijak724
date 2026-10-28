#!/usr/bin/env bash
#
# Puts the app's cron jobs in the deploy user's crontab. Safe to run on every deploy: the block between the BEGIN/END
# markers is replaced, everything else in the crontab is kept.
#
#   * * * * *  schedule:run          -> the Laravel scheduler (reports:prune ...)
#   * * * * *  keep-services-alive   -> starts the queue worker / Reverb when they are not running
#
# Usage: install-cron.sh /path/to/app /path/to/php
#
set -eu

APP_PATH="${1:?usage: install-cron.sh /path/to/app /path/to/php}"
PHP_BIN="${2:?usage: install-cron.sh /path/to/app /path/to/php}"

# Exit 1 (the deploy then uses ops/server/service-loop.sh instead) when cron cannot be used on this server.
if ! command -v crontab >/dev/null 2>&1; then
    echo "crontab is not available for this user" >&2
    exit 1
fi

if command -v pgrep >/dev/null 2>&1 && ! pgrep -x 'cron|crond|cronie' >/dev/null 2>&1; then
    echo "no cron daemon is running on this server, so cron jobs would never fire" >&2
    exit 1
fi

block="# BEGIN bijak (managed by the deploy workflow)
* * * * * cd ${APP_PATH} && ${PHP_BIN} artisan schedule:run >> /dev/null 2>&1
* * * * * bash ${APP_PATH}/ops/server/keep-services-alive.sh ${APP_PATH} ${PHP_BIN} >> /dev/null 2>&1
# END bijak"

others="$(crontab -l 2>/dev/null | sed '/^# BEGIN bijak/,/^# END bijak/d' || true)"

# A server without a crontab spool (cron not installed) fails here: the caller falls back to the loop.

if [ -n "${others}" ]; then
    printf '%s\n%s\n' "${others}" "${block}" | crontab -
else
    printf '%s\n' "${block}" | crontab -
fi

echo "cron jobs installed for ${APP_PATH}"
