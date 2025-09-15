#!/bin/bash

# EditorJS Editor Module Publishing Script
# This script helps prepare and publish the module to Packagist

set -e

echo "🚀 EditorJS Editor Module Publishing Script"
echo "=========================================="

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    echo "❌ Error: composer.json not found. Please run this script from the module root directory."
    exit 1
fi

# Check if Git is initialized
if [ ! -d ".git" ]; then
    echo "❌ Error: Git repository not initialized. Please run 'git init' first."
    exit 1
fi

# Check if we have uncommitted changes
if [ -n "$(git status --porcelain)" ]; then
    echo "⚠️  Warning: You have uncommitted changes. Please commit or stash them first."
    read -p "Continue anyway? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Get version from composer.json
VERSION=$(grep -o '"version": "[^"]*"' composer.json | cut -d'"' -f4)
echo "📦 Current version: $VERSION"

# Ask for new version
read -p "Enter new version (or press Enter to keep $VERSION): " NEW_VERSION
if [ -z "$NEW_VERSION" ]; then
    NEW_VERSION=$VERSION
fi

echo "🔄 Updating version to $NEW_VERSION..."

# Update version in composer.json
sed -i.bak "s/\"version\": \"[^\"]*\"/\"version\": \"$NEW_VERSION\"/" composer.json
rm composer.json.bak

# Update version in info.yml
sed -i.bak "s/version: [0-9.]*/version: $NEW_VERSION/" editorjs_editor.info.yml
rm editorjs_editor.info.yml.bak

# Update datestamp in composer.json
TIMESTAMP=$(date +%s)
sed -i.bak "s/\"datestamp\": \"[^\"]*\"/\"datestamp\": \"$TIMESTAMP\"/" composer.json
rm composer.json.bak

echo "✅ Version updated to $NEW_VERSION"

# Run tests
echo "🧪 Running tests..."
if command -v composer &> /dev/null; then
    composer run phpcs || echo "⚠️  PHPCS failed, but continuing..."
else
    echo "⚠️  Composer not found, skipping tests"
fi

# Commit changes
echo "📝 Committing changes..."
git add .
git commit -m "Release version $NEW_VERSION"

# Create tag
echo "🏷️  Creating tag v$NEW_VERSION..."
git tag -a "v$NEW_VERSION" -m "Release version $NEW_VERSION"

# Push to remote
echo "📤 Pushing to remote..."
read -p "Push to remote repository? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    git push origin main
    git push origin "v$NEW_VERSION"
    echo "✅ Pushed to remote repository"
else
    echo "⏭️  Skipped pushing to remote"
fi

echo ""
echo "🎉 Publishing steps completed!"
echo ""
echo "Next steps:"
echo "1. Create a GitHub repository if you haven't already"
echo "2. Push your code to GitHub"
echo "3. Go to https://packagist.org and submit your package"
echo "4. Set up Packagist webhook for automatic updates"
echo ""
echo "GitHub repository URL: https://github.com/yourusername/editorjs_editor"
echo "Packagist URL: https://packagist.org/packages/drupal/editorjs_editor"
echo ""
echo "Don't forget to update the repository URLs in composer.json!"
