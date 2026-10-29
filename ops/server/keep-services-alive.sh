#!/usr/bin/env bash
#
# Keeps the long-running processes of the app alive: the queue worker (report exports run on the `reports` queue)
# and the Reverb websocket server. A cron job of the host panel runs it every minute, so:
#   - a process that is not running is started,
#   - a process that exited (crash, or `queue:restart` / `reverb:restart` after a deploy) is started again with the new code,
#   - a process that is running is left alone.
#
# Usage: keep-services-alive.sh /path/to/app [/path/to/php]
#
set -u

APP_PATH="${1:?usage: keep-services-alive.sh /path/to/app [/path/to/php]}"
PHP_BIN="${2:-php}"

cd "${APP_PATH}" || exit 1
mkdir -p storage/run storage/logs

# Only one copy at a time (cron may overlap with the deploy).
exec 9>storage/run/keep-alive.lock
flock -n 9 || exit 0

is_running() {
    pid_file="$1"

    [ -f "${pid_file}" ] || return 1
    pid="$(cat "${pid_file}")"

    # The pid must be alive AND still be an artisan process (pids are reused).
    kill -0 "${pid}" 2>/dev/null && grep -aq artisan "/proc/${pid}/cmdline" 2>/dev/null
}

start() {
    name="$1"
    shift

    if is_running "storage/run/${name}.pid"; then
        echo "$(date '+%F %T') ${name}: already running (pid $(cat "storage/run/${name}.pid")), left alone"

        return 0
    fi

    if [ -f "storage/run/${name}.pid" ]; then
        echo "$(date '+%F %T') ${name}: not running any more (it exited or was restarted), starting it again"
    fi

    # `9>&-` closes the lock for the child, otherwise the daemon would hold it forever.
    nohup "$@" >> "storage/logs/${name}.log" 2>&1 9>&- &
    echo $! > "storage/run/${name}.pid"
    echo "$(date '+%F %T') started ${name} (pid $!)"
}

# SKIP_QUEUE_WORKER=1 when the queue is run by its own cron job (`queue:work --stop-when-empty`).
[ "${SKIP_QUEUE_WORKER:-0}" = "1" ] || start queue-worker "${PHP_BIN}" artisan queue:work --queue=reports,default --sleep=3 --tries=1 --timeout=1800 --max-time=3600

if "${PHP_BIN}" artisan list --raw 2>/dev/null | grep -q '^reverb:start'; then
    start reverb "${PHP_BIN}" artisan reverb:start
fi
