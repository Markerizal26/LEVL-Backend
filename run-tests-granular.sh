#!/bin/bash

# Ultra-granular test execution script
# Mimics GitHub Actions CI strategy

set -e

echo "🧪 Running tests with ultra-granular strategy..."
echo "================================================"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m' # No Color

FAILED=0
PASSED=0

# Function to run tests
run_test() {
    local path=$1
    local name=$2
    
    echo -e "\n📦 Testing: ${name}"
    if php artisan test "$path" --compact --no-coverage; then
        echo -e "${GREEN}✓${NC} Passed: $name"
        ((PASSED++))
    else
        echo -e "${RED}✗${NC} Failed: $name"
        ((FAILED++))
    fi
}

# Unit Tests - Core directories
run_test "tests/Unit/Foundation" "Foundation"
run_test "tests/Unit/Common" "Common"
run_test "tests/Unit/Services" "Services"
run_test "tests/Unit/Support" "Support"

# Unit Tests - Other directories
for dir in tests/Unit/*/; do
    dirname=$(basename "$dir")
    if [[ ! "$dirname" =~ ^(Foundation|Common|Services|Support)$ ]]; then
        run_test "$dir" "Unit/$dirname"
    fi
done

# Feature Tests - Per subdirectory
if [ -d "tests/Feature" ]; then
    for dir in tests/Feature/*/; do
        if [ -d "$dir" ]; then
            dirname=$(basename "$dir")
            run_test "$dir" "Feature/$dirname"
        fi
    done
fi

# Module Tests - Per module
for module in Modules/*/Tests; do
    if [ -d "$module" ]; then
        modulename=$(echo "$module" | cut -d'/' -f2)
        run_test "$module" "Module/$modulename"
    fi
done

# Summary
echo -e "\n================================================"
echo -e "📊 Test Summary:"
echo -e "   ${GREEN}Passed: $PASSED${NC}"
echo -e "   ${RED}Failed: $FAILED${NC}"
echo -e "================================================"

if [ $FAILED -gt 0 ]; then
    exit 1
fi

exit 0
