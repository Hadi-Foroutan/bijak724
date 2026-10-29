#!/usr/bin/env bash
#
# Runs one cron command and, when logging is switched on, records every run in storage/logs/cron/<name>.log:
#
#   [2026-10-29 10:15:01] START queue
#   ...what the command printed...
#   [2026-10-29 10:15:03] END queue exit=0 took=2s
#   [2026-10-29 10:16:01] SKIPPED queue: the previous run is still running
#
# Logging is controlled by CRON_LOGS in the .env of the server: `CRON_LOGS=true` turns it on, anything else (or no line)
# turns it off and the command just runs silently. It is read on every run, so no deploy or restart is needed.
#
# --no-overlap  never run two copies at once (cron starts a new one every minute): a run that finds the previous one
#               still working is skipped (and logged as SKIPPED), so jobs never pile up or hammer the database/socket.
#
# Usage: run-logged.sh [--no-overlap] NAME -- command [args...]
# Cron:  cd /path/to/app && bash ops/server/run-logged.sh --no-overlap queue -- php artisan queue:work --stop-when-empty
#
set -u

no_overlap=0
if [ "${1:-}" = "--no-overlap" ]; then
    no_overlap=1
    shift
fi

name="${1:?usage: run-logged.sh [--no-overlap] NAME -- command [args...]}"
shift
if [ "${1:-}" = "--" ]; then
    shift
fi
[ "$#" -gt 0 ] || { echo "no command given" >&2; exit 2; }

root="$(cd "$(dirname "$0")/../.." && pwd)"
cd "${root}" || exit 1
mkdir -p storage/run storage/logs/cron

logging=0
if [ -f .env ]; then
    flag="$(grep -E '^CRON_LOGS=' .env | tail -n 1 | cut -d= -f2- | tr -d "\"' ")"
    case "${flag}" in true | TRUE | 1) logging=1 ;; esac
fi
log="storage/logs/cron/${name}.log"

stamp() { date '+%Y-%m-%d %H:%M:%S'; }
note() { [ "${logging}" = "1" ] && echo "[$(stamp)] $*" >> "${log}"; return 0; }

if [ "${no_overlap}" = "1" ]; then
    if command -v flock >/dev/null 2>&1; then
        exec 8> "storage/run/cron-${name}.lock"
        if ! flock -n 8; then
            note "SKIPPED ${name}: the previous run is still running"
            exit 0
        fi
    else
        # No flock on this server: a pid file does the same job.
        pid_file="storage/run/cron-${name}.pid"
        if [ -f "${pid_file}" ] && kill -0 "$(cat "${pid_file}")" 2>/dev/null; then
            note "SKIPPED ${name}: the previous run is still running"
            exit 0
        fi
        echo $$ > "${pid_file}"
        trap 'rm -f "${pid_file}"' EXIT
    fi
fi

if [ "${logging}" != "1" ]; then
    "$@" > /dev/null 2>&1
    exit $?
fi

# Keep the file small: above 1 MB only the last 2000 lines stay.
if [ -f "${log}" ] && [ "$(wc -c < "${log}")" -gt 1048576 ]; then
    tail -n 2000 "${log}" > "${log}.tmp" && mv "${log}.tmp" "${log}"
fi

started="$(date +%s)"
note "START ${name}"
"$@" >> "${log}" 2>&1
code=$?
note "END ${name} exit=${code} took=$(( $(date +%s) - started ))s"

exit "${code}"
