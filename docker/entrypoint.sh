#!/bin/sh

set -eu

exec php artisan octane:frankenphp \
    --host="${OCTANE_HOST:-0.0.0.0}" \
    --port="${OCTANE_PORT:-8000}" \
    --workers="${OCTANE_WORKERS:-2}" \
    --max-requests="${OCTANE_MAX_REQUESTS:-1000}" \
    --log-level="${OCTANE_LOG_LEVEL:-warn}"
