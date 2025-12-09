<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2025-12-09 -->

# AGENTS.md (Classes/)

Backend PHP source code for the Extension Scanner CLI.

## 1. Overview

This directory contains all PHP source code for the extension:
- **Command/** — Symfony Console commands registered via Services.yaml
- **Dto/** — Data Transfer Objects (immutable value objects)
- **Output/** — Output formatter implementations (Strategy pattern)
- **Service/** — Core business logic services

## 2. Setup & Environment

```bash
# Start DDEV environment
ddev start

# Install dependencies
ddev composer install

# Verify PHP version (8.2+)
ddev exec php -v
```

**Requirements:** PHP 8.2+, TYPO3 12.4/13.4/14.0, Composer 2

## 3. Build & Tests

```bash
# Run all tests
ddev exec .Build/bin/phpunit -c phpunit.xml

# Run specific test file
ddev exec .Build/bin/phpunit -c phpunit.xml Tests/Unit/Dto/ScanMatchTest.php

# Static analysis
ddev exec .Build/bin/phpstan analyse

# Code style check
ddev exec .Build/bin/php-cs-fixer fix --dry-run --diff

# Code style fix
ddev exec .Build/bin/php-cs-fixer fix
```

## 4. Code Style & Conventions

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

## 5. Security & Safety

- **Never log** extension paths containing sensitive data
- **Validate all paths** before filesystem operations
- **Use TYPO3's** `GeneralUtility::getFileAbsFileName()` for path resolution
- **Escape XML output** in CheckstyleOutputFormatter (htmlspecialchars)

## 6. PR/Commit Checklist

Before committing changes to Classes/:

- [ ] `ddev exec .Build/bin/php-cs-fixer fix` passes
- [ ] `ddev exec .Build/bin/phpstan analyse` passes (level 10)
- [ ] `ddev exec .Build/bin/phpunit` passes
- [ ] New public methods have PHPDoc with `@param` and `@return`
- [ ] DTOs use `final readonly class`
- [ ] Services use constructor injection

## 7. Good vs Bad Examples

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

## 8. When Stuck

- **TYPO3 Scanner Matchers:** See `EXT:install/Classes/ExtensionScanner/Php/Matcher/`
- **Service Configuration:** Check `Configuration/Services.yaml`
- **Console Commands:** TYPO3 docs on Symfony Console integration
- **PHPStan Errors:** Often need `@phpstan-` annotations for TYPO3 core code

## 9. House Rules (Scope-Specific)

- **Internal TYPO3 APIs:** This extension uses `@internal` TYPO3 classes (matchers). Document any such usage with a comment explaining why it's acceptable.
- **Array Types:** Always use generic syntax `array<string, mixed>` or `list<Type>`
- **Match Expressions:** Prefer `match` over `switch` for simple mappings
