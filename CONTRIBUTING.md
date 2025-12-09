# Contributing to Extension Scanner CLI

Thank you for your interest in contributing to this TYPO3 extension! This document provides guidelines and information for contributors.

## Code of Conduct

This project adheres to the [Contributor Covenant Code of Conduct](CODE_OF_CONDUCT.md). By participating, you are expected to uphold this code.

## How to Contribute

### Reporting Bugs

Before creating bug reports, please check existing issues. When creating a bug report, include:

- **Clear title** describing the issue
- **Steps to reproduce** the behavior
- **Expected behavior** vs actual behavior
- **Environment details**:
  - TYPO3 version
  - PHP version
  - Extension version
  - Operating system

### Suggesting Enhancements

Enhancement suggestions are welcome! Please include:

- **Use case** description
- **Proposed solution**
- **Alternative solutions** considered
- **Additional context** (screenshots, examples)

### Pull Requests

1. **Fork** the repository
2. **Create a branch** from `main`:
   ```bash
   git checkout -b feature/your-feature-name
   ```
3. **Make your changes** following our coding standards
4. **Write/update tests** for your changes
5. **Run the test suite** to ensure everything passes
6. **Commit** with clear, descriptive messages
7. **Push** to your fork
8. **Open a Pull Request**

## Development Setup

### Prerequisites

- PHP 8.2+
- Composer 2.x
- DDEV (recommended) or local TYPO3 installation

### Using DDEV

```bash
# Clone the repository
git clone https://github.com/netresearch/extension-scanner-cli.git
cd extension-scanner-cli

# Start DDEV
ddev start

# Install dependencies
ddev composer install
```

### Running Tests

```bash
# Unit tests
ddev exec .Build/bin/phpunit -c phpunit.xml

# Or without DDEV
.Build/bin/phpunit -c phpunit.xml
```

### Code Quality Tools

```bash
# PHP CS Fixer
ddev exec .Build/bin/php-cs-fixer fix --config=Build/php-cs-fixer/.php-cs-fixer.php --dry-run --diff

# PHPStan
ddev exec .Build/bin/phpstan analyse -c Build/phpstan/phpstan.neon

# Fix coding standards
ddev exec .Build/bin/php-cs-fixer fix --config=Build/php-cs-fixer/.php-cs-fixer.php
```

## Coding Standards

### PHP

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standard
- Use [TYPO3 Coding Guidelines](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/CodingGuidelines/Index.html)
- Add type declarations to all methods
- Use strict types (`declare(strict_types=1);`)

### Commit Messages

- Use present tense ("Add feature" not "Added feature")
- Use imperative mood ("Fix bug" not "Fixes bug")
- Reference issues when applicable (`Fixes #123`)
- Keep the first line under 72 characters

Example:
```
[FEATURE] Add support for custom matcher configurations

This adds the ability to configure additional matchers beyond
the default TYPO3 core matchers.

Resolves: #42
```

### TYPO3 Commit Prefixes

- `[FEATURE]` - New functionality
- `[BUGFIX]` - Bug fixes
- `[TASK]` - Maintenance, refactoring
- `[DOCS]` - Documentation changes
- `[CLEANUP]` - Code cleanup
- `[TEST]` - Test-related changes

## Project Structure

```
extension_scanner_cli/
├── Classes/
│   ├── Command/           # Symfony console commands
│   ├── Output/            # Output formatters
│   └── Service/           # Business logic
├── Configuration/
│   └── Services.yaml      # Dependency injection
├── Documentation/         # RST documentation
├── Resources/
│   └── Public/Icons/      # Extension icon
├── Tests/
│   └── Unit/              # PHPUnit tests
└── Build/                 # Build configuration
```

## Testing Guidelines

- Write unit tests for new functionality
- Maintain existing test coverage
- Use meaningful test method names
- Follow Arrange-Act-Assert pattern

Example:
```php
/**
 * @test
 */
public function formatOutputsValidJsonStructure(): void
{
    // Arrange
    $formatter = new JsonOutputFormatter();
    $matches = [...];

    // Act
    $result = $formatter->format($output, $matches, 1, 2);

    // Assert
    $this->assertJson($result);
}
```

## Documentation

- Update documentation for user-facing changes
- Use RST format for TYPO3 documentation
- Include code examples where helpful
- Keep README.md updated

## Release Process

Releases are managed by maintainers following semantic versioning:

- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

## Questions?

- Open an issue for questions
- Contact: info@netresearch.de
- Website: https://www.netresearch.de

## License

By contributing, you agree that your contributions will be licensed under the GPL-2.0-or-later license.
