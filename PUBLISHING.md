# Publishing EditorJS Editor Module

This guide walks you through publishing the EditorJS Editor module as a Composer package on Packagist.

## Prerequisites

- GitHub account
- Packagist account
- Git installed locally
- Composer installed locally

## Step 1: Prepare the Module

### 1.1 Update Repository URLs

Edit `composer.json` and update the repository URLs:

```json
{
    "homepage": "https://github.com/markosiilak/editorjs_editor",
    "support": {
        "issues": "https://github.com/markosiilak/editorjs_editor/issues",
        "source": "https://github.com/markosiilak/editorjs_editor"
    },
    "authors": [
        {
            "name": "Marko Siilak",
            "email": "marko@siilak.com",
            "homepage": "https://github.com/markosiilak",
            "role": "Maintainer"
        }
    ]
}
```

### 1.2 Initialize Git Repository

```bash
cd web/modules/custom/editorjs_editor
git init
git add .
git commit -m "Initial commit: EditorJS Editor module v1.0.0"
```

## Step 2: Create GitHub Repository

### 2.1 Create Repository on GitHub

1. Go to [GitHub](https://github.com)
2. Click "New repository"
3. Name: `editorjs_editor`
4. Description: "EditorJS integration for Drupal with inline editing capabilities"
5. Make it public
6. Don't initialize with README (you already have one)

### 2.2 Push to GitHub

```bash
# Add remote origin
git remote add origin https://github.com/markosiilak/editorjs_editor.git

# Push to GitHub
git branch -M main
git push -u origin main
```

## Step 3: Create Packagist Account

### 3.1 Sign Up for Packagist

1. Go to [Packagist](https://packagist.org)
2. Click "Sign up"
3. Use your GitHub account to sign up
4. Verify your email

### 3.2 Submit Package

1. Go to [Packagist Submit](https://packagist.org/packages/submit)
2. Enter repository URL: `https://github.com/YOUR_USERNAME/editorjs_editor`
3. Click "Check"
4. Review package information
5. Click "Submit"

## Step 4: Set Up Automatic Updates

### 4.1 Enable Packagist Webhook

1. Go to your package page on Packagist
2. Click "Settings"
3. Enable "Update" webhook
4. Copy the webhook URL

### 4.2 Add Webhook to GitHub

1. Go to your GitHub repository
2. Click "Settings" > "Webhooks"
3. Click "Add webhook"
4. Paste the Packagist webhook URL
5. Set content type to "application/json"
6. Select "Just the push event"
7. Click "Add webhook"

## Step 5: Version Management

### 5.1 Using the Publishing Script

The module includes a publishing script to help with version management:

```bash
./scripts/publish.sh
```

This script will:
- Update version numbers
- Run tests
- Commit changes
- Create Git tags
- Push to remote

### 5.2 Manual Version Management

For manual version management:

```bash
# Update version in composer.json
# Update version in editorjs_editor.info.yml
# Update CHANGELOG.md

# Commit and tag
git add .
git commit -m "Release version X.Y.Z"
git tag -a "vX.Y.Z" -m "Release version X.Y.Z"
git push origin main
git push origin "vX.Y.Z"
```

## Step 6: Testing Installation

### 6.1 Test with Composer

Create a test project to verify installation:

```bash
# Create test directory
mkdir test-installation
cd test-installation

# Create composer.json
cat > composer.json << EOF
{
    "name": "test/editorjs-test",
    "type": "project",
    "require": {
        "drupal/editorjs_editor": "^1.0"
    },
    "repositories": [
        {
            "type": "composer",
            "url": "https://packages.drupal.org/8"
        }
    ]
}
EOF

# Install the module
composer install
```

### 6.2 Verify Installation

Check that the module is installed correctly:

```bash
# Check if module files exist
ls -la vendor/drupal/editorjs_editor/

# Check composer.json
cat vendor/drupal/editorjs_editor/composer.json
```

## Step 7: Documentation and Support

### 7.1 Update Documentation

- Ensure README.md is comprehensive
- Update CHANGELOG.md with each release
- Add installation instructions
- Include usage examples

### 7.2 Set Up Support Channels

- Enable GitHub Issues
- Set up GitHub Discussions
- Create contribution guidelines
- Add code of conduct

## Step 8: Maintenance

### 8.1 Regular Updates

- Monitor for security updates
- Update dependencies regularly
- Respond to issues and pull requests
- Release bug fixes promptly

### 8.2 Community Engagement

- Participate in Drupal community
- Share on social media
- Write blog posts
- Present at conferences

## Troubleshooting

### Common Issues

1. **Package not found on Packagist**
   - Check repository URL is correct
   - Verify GitHub repository is public
   - Ensure composer.json is valid

2. **Webhook not working**
   - Check webhook URL is correct
   - Verify GitHub webhook is active
   - Check Packagist webhook settings

3. **Version not updating**
   - Ensure Git tag is pushed
   - Check Packagist webhook is working
   - Verify composer.json version is updated

### Getting Help

- Check Packagist documentation
- Review GitHub webhook documentation
- Ask in Drupal community forums
- Create GitHub issues for bugs

## Success Checklist

- [ ] Module code is complete and tested
- [ ] Documentation is comprehensive
- [ ] GitHub repository is created and public
- [ ] Packagist package is submitted and approved
- [ ] Webhook is set up for automatic updates
- [ ] Installation is tested and working
- [ ] Support channels are established
- [ ] Community engagement is active

## Next Steps

After successful publishing:

1. Announce the release on social media
2. Share in Drupal community forums
3. Write a blog post about the module
4. Consider presenting at Drupal events
5. Monitor for user feedback and issues
6. Plan future enhancements

Congratulations on publishing your Drupal module! 🎉
