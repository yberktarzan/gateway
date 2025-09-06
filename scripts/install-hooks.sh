#!/bin/bash

# Git hooks setup script
# This script copies the hooks from scripts/ directory to .git/hooks/

echo "🔧 Setting up Git hooks..."

# Create hooks directory if it doesn't exist
mkdir -p .git/hooks

# Copy hooks
cp scripts/commit-msg .git/hooks/commit-msg
cp scripts/pre-commit .git/hooks/pre-commit

# Make them executable
chmod +x .git/hooks/commit-msg
chmod +x .git/hooks/pre-commit

echo "✅ Git hooks installed successfully!"
echo ""
echo "Installed hooks:"
echo "  - commit-msg: Validates semantic commit messages"
echo "  - pre-commit: Runs Pint and PHPStan on staged files"
echo ""
echo "You can now commit with confidence! 🚀"
