#!/bin/bash

################################################################################
# GitLab CI/CD Rollback Script
# Rollback ke release sebelumnya
################################################################################

set -e
set -u

################################################################################
# Configuration
################################################################################

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

DEPLOY_USER="${DEPLOY_USER:-deploy}"
DEPLOY_SERVER="${DEPLOY_SERVER:-localhost}"
DEPLOY_PATH="${DEPLOY_PATH:-/var/www/ta-prep-lsp-be}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.4-fpm}"
CURRENT_PATH="${DEPLOY_PATH}/current"
RELEASES_PATH="${DEPLOY_PATH}/releases"

################################################################################
# Helper Functions
################################################################################

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

execute_remote() {
    ssh "${DEPLOY_USER}@${DEPLOY_SERVER}" "$1"
}

################################################################################
# Rollback Process
################################################################################

log_info "Starting rollback process..."

# Get current release
CURRENT_RELEASE=$(execute_remote "readlink ${CURRENT_PATH} | xargs basename")
log_info "Current release: ${CURRENT_RELEASE}"

# Get list of releases (sorted by date, newest first)
RELEASES=$(execute_remote "cd ${RELEASES_PATH} && ls -t")
RELEASES_ARRAY=($RELEASES)

# Find previous release
PREVIOUS_RELEASE=""
FOUND_CURRENT=0

for release in "${RELEASES_ARRAY[@]}"; do
    if [ $FOUND_CURRENT -eq 1 ]; then
        PREVIOUS_RELEASE=$release
        break
    fi
    if [ "$release" == "$CURRENT_RELEASE" ]; then
        FOUND_CURRENT=1
    fi
done

if [ -z "$PREVIOUS_RELEASE" ]; then
    log_error "No previous release found for rollback!"
    exit 1
fi

log_info "Rolling back to: ${PREVIOUS_RELEASE}"

# Confirm rollback
PREVIOUS_RELEASE_PATH="${RELEASES_PATH}/${PREVIOUS_RELEASE}"

# Switch symlink to previous release
execute_remote "ln -sfn ${PREVIOUS_RELEASE_PATH} ${CURRENT_PATH}"

log_info "Symlink updated: ${CURRENT_PATH} -> ${PREVIOUS_RELEASE_PATH}"

# Reload services
log_info "Reloading services..."

execute_remote "sudo systemctl reload ${PHP_FPM_SERVICE}" || log_warning "Failed to reload PHP-FPM"
execute_remote "sudo systemctl reload nginx" || log_warning "Failed to reload Nginx"
execute_remote "cd ${CURRENT_PATH} && php artisan queue:restart" || log_warning "Queue restart failed"

# Clear caches
log_info "Clearing caches..."
execute_remote "cd ${CURRENT_PATH} && php artisan config:clear"
execute_remote "cd ${CURRENT_PATH} && php artisan cache:clear"
execute_remote "cd ${CURRENT_PATH} && php artisan route:clear"
execute_remote "cd ${CURRENT_PATH} && php artisan view:clear"

# Re-cache
execute_remote "cd ${CURRENT_PATH} && php artisan config:cache"
execute_remote "cd ${CURRENT_PATH} && php artisan route:cache"
execute_remote "cd ${CURRENT_PATH} && php artisan view:cache"

log_info "✅ Rollback completed successfully!"
log_info "Application rolled back to: ${PREVIOUS_RELEASE}"

# Display current version
execute_remote "cd ${CURRENT_PATH} && php artisan --version"

exit 0
