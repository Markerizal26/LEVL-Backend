#!/usr/bin/env bash

# Local VPS storage diagnostic script.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_DIR"

if [[ ! -f .env ]]; then
    echo ".env not found" >&2
    exit 1
fi

echo "Project: $PROJECT_DIR"
echo "Filesystem disk: $(grep '^FILESYSTEM_DISK=' .env | cut -d '=' -f2- || true)"
echo "Media disk: $(grep '^MEDIA_DISK=' .env | cut -d '=' -f2- || true)"
echo

php artisan storage:diagnose --test-upload

if [[ -d storage/app ]]; then
    echo
    echo "Local storage usage:"
    du -sh storage/app
    df -h storage/app
fi
