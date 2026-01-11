#!/bin/bash

################################################################################
# Health Check Script
# Verify aplikasi berjalan dengan baik setelah deployment
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
APP_URL="${APP_URL:-https://levl-backend.site}"
HEALTH_ENDPOINT="${HEALTH_ENDPOINT:-/api/health}"

CURRENT_PATH="${DEPLOY_PATH}/current"

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
# Health Checks
################################################################################

log_info "Starting health checks..."

HEALTH_PASSED=0
HEALTH_FAILED=0

# 1. Check if application directory exists
log_info "Checking application directory..."
if execute_remote "[ -d ${CURRENT_PATH} ]"; then
    log_info "✓ Application directory exists"
    ((HEALTH_PASSED++))
else
    log_error "✗ Application directory not found"
    ((HEALTH_FAILED++))
fi

# 2. Check if .env exists
log_info "Checking environment configuration..."
if execute_remote "[ -f ${CURRENT_PATH}/.env ]"; then
    log_info "✓ Environment file exists"
    ((HEALTH_PASSED++))
else
    log_error "✗ Environment file not found"
    ((HEALTH_FAILED++))
fi

# 3. Check Laravel version
log_info "Checking Laravel installation..."
if execute_remote "cd ${CURRENT_PATH} && php artisan --version"; then
    log_info "✓ Laravel is running"
    ((HEALTH_PASSED++))
else
    log_error "✗ Laravel check failed"
    ((HEALTH_FAILED++))
fi

# 4. Check database connection
log_info "Checking database connection..."
if execute_remote "cd ${CURRENT_PATH} && php artisan db:show 2>/dev/null" || execute_remote "cd ${CURRENT_PATH} && php artisan tinker --execute='DB::connection()->getPdo();' 2>/dev/null"; then
    log_info "✓ Database connection successful"
    ((HEALTH_PASSED++))
else
    log_warning "⚠ Database connection check inconclusive"
    ((HEALTH_FAILED++))
fi

# 5. Check storage permissions
log_info "Checking storage permissions..."
if execute_remote "[ -w ${CURRENT_PATH}/storage/logs ]"; then
    log_info "✓ Storage is writable"
    ((HEALTH_PASSED++))
else
    log_error "✗ Storage is not writable"
    ((HEALTH_FAILED++))
fi

# 6. Check if public directory exists
log_info "Checking public directory..."
if execute_remote "[ -d ${CURRENT_PATH}/public ]"; then
    log_info "✓ Public directory exists"
    ((HEALTH_PASSED++))
else
    log_error "✗ Public directory not found"
    ((HEALTH_FAILED++))
fi

# 7. HTTP Health Check (if health endpoint exists)
log_info "Checking HTTP endpoint..."
HTTP_STATUS=$(curl -o /dev/null -s -w "%{http_code}" "${APP_URL}${HEALTH_ENDPOINT}" || echo "000")

if [ "$HTTP_STATUS" == "200" ] || [ "$HTTP_STATUS" == "302" ]; then
    log_info "✓ HTTP endpoint responding (Status: ${HTTP_STATUS})"
    ((HEALTH_PASSED++))
else
    log_warning "⚠ HTTP endpoint check returned status: ${HTTP_STATUS}"
    # Try homepage
    HTTP_STATUS=$(curl -o /dev/null -s -w "%{http_code}" "${APP_URL}" || echo "000")
    if [ "$HTTP_STATUS" == "200" ] || [ "$HTTP_STATUS" == "302" ]; then
        log_info "✓ Homepage responding (Status: ${HTTP_STATUS})"
        ((HEALTH_PASSED++))
    else
        log_warning "⚠ Homepage check returned status: ${HTTP_STATUS}"
        ((HEALTH_FAILED++))
    fi
fi

# 8. Check queue workers (optional)
log_info "Checking queue configuration..."
if execute_remote "cd ${CURRENT_PATH} && php artisan queue:failed 2>/dev/null"; then
    log_info "✓ Queue system accessible"
    ((HEALTH_PASSED++))
else
    log_warning "⚠ Queue check inconclusive"
fi

################################################################################
# Summary
################################################################################

log_info "================================"
log_info "Health Check Summary"
log_info "================================"
log_info "Passed: ${HEALTH_PASSED}"
log_info "Failed: ${HEALTH_FAILED}"
log_info "================================"

if [ $HEALTH_FAILED -gt 2 ]; then
    log_error "⚠️  Health check failed with ${HEALTH_FAILED} failures"
    log_error "Please investigate the issues above"
    exit 1
else
    log_info "✅ Health check passed!"
    exit 0
fi
