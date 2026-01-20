#!/bin/bash

# Script to scan for hardcoded strings in the codebase
# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}=== Scanning for Hardcoded Strings ===${NC}\n"

# 1. Check Services for hardcoded Exception messages
echo -e "${GREEN}1. Checking Services for hardcoded Exception messages...${NC}"
SERVICES_COUNT=$(grep -rn --include="*.php" -E "(throw new|new BusinessException|new Exception)\s*\(" Modules/*/app/Services/ \
  | grep -v "__(" \
  | grep -E "(['\"])[A-Z][a-zA-Z\s,\.!?]{5,}\1" \
  | wc -l | tr -d ' ')

if [ "$SERVICES_COUNT" -gt 0 ]; then
  echo -e "${RED}   Found $SERVICES_COUNT hardcoded exception messages${NC}"
  grep -rn --include="*.php" -E "(throw new|new BusinessException|new Exception)\s*\(" Modules/*/app/Services/ \
    | grep -v "__(" \
    | grep -E "(['\"])[A-Z][a-zA-Z\s,\.!?]{5,}\1" \
    | head -10
  echo ""
else
  echo -e "   ✅ No hardcoded exceptions found in Services"
fi

# 2. Check Request files for hardcoded validation messages
echo -e "\n${GREEN}2. Checking Request files for hardcoded validation messages...${NC}"
REQUESTS_COUNT=$(grep -rn --include="*.php" "public function messages()" Modules/*/app/Http/Requests/ -A 50 \
  | grep -E "=>\s*(['\"])[A-Z][a-zA-Z\s]{5,}\1" \
  | grep -v "__(" \
  | wc -l | tr -d ' ')

if [ "$REQUESTS_COUNT" -gt 0 ]; then
  echo -e "${RED}   Found $REQUESTS_COUNT hardcoded validation messages${NC}"
  grep -rn --include="*.php" "public function messages()" Modules/*/app/Http/Requests/ -A 50 \
    | grep -E "=>\s*(['\"])[A-Z][a-zA-Z\s]{5,}\1" \
    | grep -v "__(" \
    | head -10
  echo ""
else
  echo -e "   ✅ No hardcoded validation messages found in Requests"
fi

# 3. Check Controllers for hardcoded response messages
echo -e "\n${GREEN}3. Checking Controllers for hardcoded response messages...${NC}"
CONTROLLERS_COUNT=$(grep -rn --include="*.php" -E "(return response|return \\\$this->success|return \\\$this->error)" Modules/*/app/Http/Controllers/ \
  | grep -E "(['\"])[A-Z][a-zA-Z\s,\.!?]{10,}\1" \
  | grep -v "__(" \
  | wc -l | tr -d ' ')

if [ "$CONTROLLERS_COUNT" -gt 0 ]; then
  echo -e "${RED}   Found $CONTROLLERS_COUNT hardcoded response messages${NC}"
  grep -rn --include="*.php" -E "(return response|return \\\$this->success|return \\\$this->error)" Modules/*/app/Http/Controllers/ \
    | grep -E "(['\"])[A-Z][a-zA-Z\s,\.!?]{10,}\1" \
    | grep -v "__(" \
    | head -10
  echo ""
else
  echo -e "   ✅ No hardcoded response messages found in Controllers"
fi

# 4. Check for fail() in validation closures
echo -e "\n${GREEN}4. Checking validation closures for hardcoded fail() messages...${NC}"
FAIL_COUNT=$(grep -rn --include="*.php" '\$fail(' Modules/*/app/Http/Requests/ \
  | grep -E "fail\((['\"])[A-Z][a-zA-Z\s]{5,}\1" \
  | grep -v "__(" \
  | wc -l | tr -d ' ')

if [ "$FAIL_COUNT" -gt 0 ]; then
  echo -e "${RED}   Found $FAIL_COUNT hardcoded fail() messages${NC}"
  grep -rn --include="*.php" '\$fail(' Modules/*/app/Http/Requests/ \
    | grep -E "fail\((['\"])[A-Z][a-zA-Z\s]{5,}\1" \
    | grep -v "__(" \
    | head -10
  echo ""
else
  echo -e "   ✅ No hardcoded fail() messages found"
fi

# 5. Check for abort() with hardcoded messages
echo -e "\n${GREEN}5. Checking for abort() with hardcoded messages...${NC}"
ABORT_COUNT=$(grep -rn --include="*.php" 'abort(' Modules/ \
  | grep -v "abort(404)" \
  | grep -v "abort(403)" \
  | grep -v "abort(401)" \
  | grep -E "abort\([0-9]+,\s*(['\"])[A-Z]" \
  | grep -v "__(" \
  | wc -l | tr -d ' ')

if [ "$ABORT_COUNT" -gt 0 ]; then
  echo -e "${RED}   Found $ABORT_COUNT hardcoded abort messages${NC}"
  grep -rn --include="*.php" 'abort(' Modules/ \
    | grep -v "abort(404)" \
    | grep -v "abort(403)" \
    | grep -v "abort(401)" \
    | grep -E "abort\([0-9]+,\s*(['\"])[A-Z]" \
    | grep -v "__(" \
    | head -10
  echo ""
else
  echo -e "   ✅ No hardcoded abort messages found"
fi

# Summary
echo -e "\n${YELLOW}=== Summary ===${NC}"
TOTAL=$((SERVICES_COUNT + REQUESTS_COUNT + CONTROLLERS_COUNT + FAIL_COUNT + ABORT_COUNT))

if [ "$TOTAL" -eq 0 ]; then
  echo -e "${GREEN}✅ No hardcoded strings found! All translations implemented correctly.${NC}"
else
  echo -e "${RED}❌ Total hardcoded strings found: $TOTAL${NC}"
  echo -e "   - Services: $SERVICES_COUNT"
  echo -e "   - Requests: $REQUESTS_COUNT"
  echo -e "   - Controllers: $CONTROLLERS_COUNT"
  echo -e "   - fail() calls: $FAIL_COUNT"
  echo -e "   - abort() calls: $ABORT_COUNT"
fi

echo ""
