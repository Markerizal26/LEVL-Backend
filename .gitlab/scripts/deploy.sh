#!/bin/bash

################################################################################
# GitLab CI/CD Deployment Script
# Zero-Downtime Deployment untuk Laravel di VPS
################################################################################

set -e  # Exit on error
set -u  # Exit on undefined variable

################################################################################
# Configuration
################################################################################

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Deployment variables (dari GitLab CI/CD Variables)
DEPLOY_USER="${DEPLOY_USER:-deploy}"
DEPLOY_SERVER="${DEPLOY_SERVER:-localhost}"
DEPLOY_PATH="${DEPLOY_PATH:-/var/www/ta-prep-lsp-be}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.4-fpm}"
WEB_SERVER_USER="${WEB_SERVER_USER:-www-data}"

# Release configuration
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
RELEASE_NAME="${TIMESTAMP}"
RELEASE_PATH="${DEPLOY_PATH}/releases/${RELEASE_NAME}"
SHARED_PATH="${DEPLOY_PATH}/shared"
CURRENT_PATH="${DEPLOY_PATH}/current"

# Keep last 5 releases for rollback
KEEP_RELEASES=5

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
# Pre-deployment Checks
################################################################################

log_info "Starting deployment to ${DEPLOY_SERVER}..."
log_info "Release: ${RELEASE_NAME}"

# Check SSH connection
log_info "Checking SSH connection..."
if ! execute_remote "echo 'SSH connection successful'"; then
    log_error "Failed to connect to ${DEPLOY_SERVER}"
    exit 1
fi

################################################################################
# Setup Directory Structure
################################################################################

log_info "Setting up directory structure..."

execute_remote "mkdir -p ${DEPLOY_PATH}/{releases,shared,backups/database}"
execute_remote "mkdir -p ${SHARED_PATH}/{storage/{app,framework,logs},bootstrap/cache}"
execute_remote "mkdir -p ${RELEASE_PATH}"

# Set proper permissions for shared directories
execute_remote "chmod -R 775 ${SHARED_PATH}/storage"
execute_remote "chmod -R 775 ${SHARED_PATH}/bootstrap/cache"

################################################################################
# Upload Code
################################################################################

log_info "Uploading application files..."

# Exclude files yang tidak perlu di production
rsync -azP --delete \
    --exclude='.git' \
    --exclude='.gitlab' \
    --exclude='node_modules' \
    --exclude='.env' \
    --exclude='.env.testing' \
    --exclude='storage' \
    --exclude='bootstrap/cache' \
    --exclude='tests' \
    --exclude='.phpunit.cache' \
    --exclude='phpstan-report.txt' \
    --exclude='phpstan-report.json' \
    --exclude='semgrep-report.json' \
    ./ "${DEPLOY_USER}@${DEPLOY_SERVER}:${RELEASE_PATH}/"

log_info "Code upload completed"

################################################################################
# Setup Shared Resources
################################################################################

log_info "Setting up shared resources..."

# Create .env if not exists
execute_remote "if [ ! -f ${SHARED_PATH}/.env ]; then cp ${RELEASE_PATH}/.env.example ${SHARED_PATH}/.env; fi"

# Create symlinks to shared resources
execute_remote "rm -rf ${RELEASE_PATH}/storage"
execute_remote "ln -s ${SHARED_PATH}/storage ${RELEASE_PATH}/storage"

execute_remote "rm -rf ${RELEASE_PATH}/bootstrap/cache"
execute_remote "ln -s ${SHARED_PATH}/bootstrap/cache ${RELEASE_PATH}/bootstrap/cache"

execute_remote "rm -f ${RELEASE_PATH}/.env"
execute_remote "ln -s ${SHARED_PATH}/.env ${RELEASE_PATH}/.env"

################################################################################
# Install Dependencies
################################################################################

log_info "Installing Composer dependencies..."

execute_remote "cd ${RELEASE_PATH} && composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --no-progress"

################################################################################
# Build Assets (if not built in CI)
################################################################################

# Assets sudah di-build di CI stage, jadi tinggal verify
log_info "Verifying built assets..."

execute_remote "if [ ! -d ${RELEASE_PATH}/public/build ]; then echo 'WARNING: No built assets found'; fi"

################################################################################
# Database Backup & Migrations
################################################################################

log_info "Creating database backup..."

# Backup database sebelum migration
BACKUP_FILE="${DEPLOY_PATH}/backups/database/backup_${TIMESTAMP}.sql"
execute_remote "cd ${RELEASE_PATH} && php artisan db:backup --path=${BACKUP_FILE} || echo 'Database backup skipped'"

log_info "Running database migrations..."

execute_remote "cd ${RELEASE_PATH} && php artisan migrate --force" || {
    log_error "Migration failed! Rolling back..."
    execute_remote "cd ${RELEASE_PATH} && php artisan migrate:rollback --force"
    exit 1
}

################################################################################
# Laravel Optimization
################################################################################

log_info "Optimizing Laravel..."

# Clear all caches
execute_remote "cd ${RELEASE_PATH} && php artisan config:clear"
execute_remote "cd ${RELEASE_PATH} && php artisan cache:clear"
execute_remote "cd ${RELEASE_PATH} && php artisan route:clear"
execute_remote "cd ${RELEASE_PATH} && php artisan view:clear"

# Cache configuration & routes for production
execute_remote "cd ${RELEASE_PATH} && php artisan config:cache"
execute_remote "cd ${RELEASE_PATH} && php artisan route:cache"
execute_remote "cd ${RELEASE_PATH} && php artisan view:cache"

# Optimize Composer autoloader
execute_remote "cd ${RELEASE_PATH} && composer dump-autoload --optimize"

################################################################################
# Set Permissions
################################################################################

log_info "Setting proper permissions..."

execute_remote "chown -R ${DEPLOY_USER}:${WEB_SERVER_USER} ${RELEASE_PATH}"
execute_remote "chmod -R 755 ${RELEASE_PATH}"
execute_remote "chmod -R 775 ${RELEASE_PATH}/storage"
execute_remote "chmod -R 775 ${RELEASE_PATH}/bootstrap/cache"

################################################################################
# Switch to New Release (Zero-Downtime)
################################################################################

log_info "Switching to new release..."

# Atomic symlink swap
execute_remote "ln -sfn ${RELEASE_PATH} ${CURRENT_PATH}"

log_info "Symlink updated: ${CURRENT_PATH} -> ${RELEASE_PATH}"

################################################################################
# Reload Services
################################################################################

log_info "Reloading services..."

# Reload PHP-FPM
execute_remote "sudo systemctl reload ${PHP_FPM_SERVICE}" || log_warning "Failed to reload PHP-FPM, continuing..."

# Reload Nginx
execute_remote "sudo systemctl reload nginx" || log_warning "Failed to reload Nginx, continuing..."

# Restart queue workers (gracefully)
execute_remote "cd ${CURRENT_PATH} && php artisan queue:restart" || log_warning "Queue restart failed, continuing..."

################################################################################
# Cleanup Old Releases
################################################################################

log_info "Cleaning up old releases..."

execute_remote "cd ${DEPLOY_PATH}/releases && ls -t | tail -n +$((KEEP_RELEASES + 1)) | xargs -r rm -rf"

log_info "Kept last ${KEEP_RELEASES} releases for rollback"

################################################################################
# Post-Deployment
################################################################################

log_info "Deployment completed successfully!"
log_info "Release: ${RELEASE_NAME}"
log_info "Path: ${RELEASE_PATH}"
log_info "Current: ${CURRENT_PATH}"

# Display deployment info
execute_remote "cd ${CURRENT_PATH} && php artisan --version"

log_info "✅ Deployment finished!"

exit 0
