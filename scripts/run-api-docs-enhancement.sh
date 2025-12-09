#!/bin/bash

# API Documentation Enhancement Runner
# This script runs the PHP automation script and provides helpful output

echo "🚀 API Documentation Auto-Enhancement"
echo "======================================"
echo ""
echo "This will automatically add @authenticated tags to all controller methods."
echo ""
read -p "Continue? (y/n) " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]
then
    echo ""
    echo "Running enhancement script..."
    echo ""
    
    php scripts/enhance-api-docs.php
    
    echo ""
    echo "✅ Enhancement complete!"
    echo ""
    echo "Next steps:"
    echo "1. Review changes: git diff"
    echo "2. Test in Scramble: http://localhost:8000/docs/api"
    echo "3. Commit if satisfied: git add . && git commit -m 'docs: auto-enhanced API documentation'"
    echo ""
else
    echo "Cancelled."
fi
