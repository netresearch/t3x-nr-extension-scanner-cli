<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2026-08-19 -->

# AGENTS.md (Classes/)

Backend PHP source code for the Extension Scanner CLI.

## Overview

This directory contains all PHP source code for the extension:
- **Command/** — Symfony Console commands registered via Services.yaml
- **Dto/** — Data Transfer Objects (immutable value objects)
- **Output/** — Output formatter implementations (Strategy pattern)
- **Service/** — Core business logic services

## Setup & Environment

```bash
# Start DDEV environment
ddev start

# Install dependencies
ddev composer install

# Verify PHP version (8.2+)
ddev exec php -v
```

**Requirements:** PHP 8.2+, TYPO3 12.4/13.4/14.3, Composer 2

## Build & Tests

```bash
# Run all tests (unit + functional)
make test

# Run specific test file
.Build/bin/phpunit -c phpunit.xml Tests/Unit/Dto/ScanMatchTest.php

# Static analysis (config: Build/phpstan/phpstan.neon)
composer ci:test:php:phpstan

# Code style check (config: .php-cs-fixer.dist.php)
composer ci:test:php:cgl

# Code style fix
composer ci:cgl
```

Inside DDEV, prefix commands with `ddev exec`.

## Code Style & Conventions

### PHP Standards
- **PSR-12** coding style (enforced by php-cs-fixer)
- **PHPStan level 10** (strictest level)
- **Strict types** required: `declare(strict_types=1);`

### Naming Conventions
- Classes: `PascalCase`
- Methods/Properties: `camelCase`
- Constants: `SCREAMING_SNAKE_CASE`
- Interfaces: suffix with `Interface`

### DTO Pattern
```php
// Good: Immutable readonly DTO
final readonly class ScanMatch
{
    public function __construct(
        public string $file,
        public int $line,
        public string $message,
    ) {}
}

// Bad: Mutable class with setters
class ScanMatch
{
    private string $file;
    public function setFile(string $file): void { ... }
}
```

### Service Pattern
```php
// Good: Constructor injection
public function __construct(
    private readonly ExtensionScannerService $scannerService,
    private readonly PackageManager $packageManager,
) {
    parent::__construct();
}

// Bad: Service locator
$service = GeneralUtility::makeInstance(ExtensionScannerService::class);
```

## Security & Safety

- **Never log** extension paths containing sensitive data
- **Validate all paths** before filesystem operations
- **Use TYPO3's** `GeneralUtility::getFileAbsFileName()` for path resolution
- **Escape XML output** in CheckstyleOutputFormatter (htmlspecialchars)

## PR/Commit Checklist

Before committing changes to Classes/:

- [ ] `composer ci:test:php:cgl` passes
- [ ] `composer ci:test:php:phpstan` passes (level 10)
- [ ] `composer ci:test:php:unit` passes
- [ ] New public methods have PHPDoc with `@param` and `@return`
- [ ] DTOs use `final readonly class`
- [ ] Services use constructor injection

## Good vs Bad Examples

### Adding a New Output Formatter

```php
// Good: Implements interface, follows pattern
final class XmlOutputFormatter implements OutputFormatterInterface
{
    public function format(
        OutputInterface $output,
        array $allMatches,
        int $totalStrong,
        int $totalWeak,
    ): void {
        // Implementation
    }
}

// Bad: Missing interface, unclear contract
class XmlOutput
{
    public function render($matches) { ... }
}
```

### Working with ScanMatch

```php
// Good: Use factory method for raw data
$match = ScanMatch::fromMatcherOutput($rawMatch, $relativeFile, $absolutePath, $matcherClass);

// Good: Use type-safe accessors
if ($match->isStrong()) {
    $strongCount++;
}

// Bad: Direct array access
$indicator = $rawMatch['indicator'] ?? 'strong';
```

## When Stuck

- **TYPO3 Scanner Matchers:** See `EXT:install/Classes/ExtensionScanner/Php/Matcher/`
- **Service Configuration:** Check `Configuration/Services.yaml`
- **Console Commands:** TYPO3 docs on Symfony Console integration
- **PHPStan Errors:** Often need `@phpstan-` annotations for TYPO3 core code

## House Rules (Scope-Specific)

- **Internal TYPO3 APIs:** This extension uses `@internal` TYPO3 classes (matchers). Document any such usage with a comment explaining why it's acceptable.
- **Array Types:** Always use generic syntax `array<string, mixed>` or `list<Type>`
- **Match Expressions:** Prefer `match` over `switch` for simple mappings
