# Contributing to EditorJS Editor

Thank you for your interest in contributing to the EditorJS Editor module! This document provides guidelines and information for contributors.

## Getting Started

1. Fork the repository on GitHub
2. Clone your fork locally
3. Create a new branch for your feature or bugfix
4. Make your changes
5. Test your changes thoroughly
6. Submit a pull request

## Development Setup

### Prerequisites
- PHP 8.1 or higher
- Composer
- Drupal 10+ or 11+
- Node.js (for JavaScript development)

### Installation
```bash
# Clone the repository
git clone https://github.com/yourusername/editorjs_editor.git
cd editorjs_editor

# Install dependencies
composer install

# Install development dependencies
composer install --dev
```

## Coding Standards

### PHP
- Follow Drupal coding standards
- Use PSR-4 autoloading
- Write comprehensive docblocks
- Include unit tests for new functionality

### JavaScript
- Use ES6+ features
- Follow Drupal JavaScript standards
- Include JSDoc comments
- Test in multiple browsers

### CSS
- Use BEM methodology
- Follow Drupal CSS standards
- Ensure responsive design
- Test accessibility

## Testing

### Running Tests
```bash
# Run PHPCS
composer run phpcs

# Fix coding standards issues
composer run phpcbf

# Run unit tests
composer run test
```

### Manual Testing
1. Install the module on a test Drupal site
2. Create content with EditorJS fields
3. Test inline editing functionality
4. Verify AJAX save functionality
5. Test responsive design
6. Check accessibility compliance

## Pull Request Process

### Before Submitting
- [ ] Code follows coding standards
- [ ] Tests pass
- [ ] Documentation is updated
- [ ] Changelog is updated
- [ ] Manual testing completed

### Pull Request Template
```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Unit tests pass
- [ ] Manual testing completed
- [ ] Browser compatibility tested

## Checklist
- [ ] Code follows coding standards
- [ ] Self-review completed
- [ ] Documentation updated
- [ ] Changelog updated
```

## Issue Reporting

### Bug Reports
When reporting bugs, please include:
- Drupal version
- PHP version
- Module version
- Steps to reproduce
- Expected behavior
- Actual behavior
- Error messages or logs

### Feature Requests
When requesting features, please include:
- Use case description
- Proposed solution
- Alternative solutions considered
- Impact assessment

## Release Process

### Versioning
We follow [Semantic Versioning](https://semver.org/):
- MAJOR: Breaking changes
- MINOR: New features (backward compatible)
- PATCH: Bug fixes (backward compatible)

### Release Steps
1. Update version numbers in all relevant files
2. Update CHANGELOG.md
3. Create Git tag
4. Update Packagist
5. Announce release

## Community Guidelines

### Code of Conduct
- Be respectful and inclusive
- Focus on constructive feedback
- Help others learn and grow
- Follow Drupal community guidelines

### Communication
- Use GitHub issues for bug reports and feature requests
- Use GitHub discussions for general questions
- Be clear and concise in communications
- Provide context and examples when helpful

## Recognition

Contributors will be recognized in:
- README.md contributors section
- Release notes
- Module documentation

Thank you for contributing to the EditorJS Editor module!
