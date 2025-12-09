<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2025-12-09 -->

# AGENTS.md (Tests/)

Testing infrastructure for the Extension Scanner CLI.

## 1. Overview

This directory contains all tests for the extension:
- **Unit/** — Fast, isolated unit tests (no TYPO3 bootstrap)
- **Functional/** — Integration tests requiring TYPO3 environment

## 2. Setup & Environment

```bash
# Install dev dependencies
ddev composer install

# Verify PHPUnit
ddev exec .Build/bin/phpunit --version
```

**Framework:** PHPUnit 10.5+ with TYPO3 Testing Framework 8.x

## 3. Build & Tests

```bash
# Run all tests
ddev exec .Build/bin/phpunit -c phpunit.xml

# Run unit tests only
ddev exec .Build/bin/phpunit -c phpunit.xml --testsuite=Unit

# Run functional tests only
ddev exec .Build/bin/phpunit -c phpunit.xml --testsuite=Functional

# Run with coverage (PCOV)
ddev exec .Build/bin/phpunit -c phpunit.xml --coverage-text

# Run specific test class
ddev exec .Build/bin/phpunit -c phpunit.xml Tests/Unit/Dto/ScanMatchTest.php

# Run specific test method
ddev exec .Build/bin/phpunit -c phpunit.xml --filter testIsStrongReturnsTrueForStrongIndicator
```

## 4. Code Style & Conventions

### PHPUnit 10+ Attributes

```php
// Good: Use attributes (PHPUnit 10+)
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(ScanMatch::class)]
final class ScanMatchTest extends TestCase
{
    #[Test]
    public function constructorSetsAllProperties(): void
    {
        // Test implementation
    }
}

// Bad: Legacy annotations
/**
 * @covers ScanMatch
 * @test
 */
public function constructorSetsAllProperties(): void { ... }
```

### Test Naming

```php
// Good: Descriptive method names
public function isStrongReturnsTrueForStrongIndicator(): void
public function fromMatcherOutputHandlesMissingFields(): void
public function toArrayReturnsCorrectStructure(): void

// Bad: Vague names
public function testMatch(): void
public function testOutput(): void
```

### Assertion Style

```php
// Good: Self-referencing assertions
self::assertSame('expected', $actual);
self::assertTrue($match->isStrong());
self::assertCount(2, $matches);

// Bad: $this assertions (deprecated in strict mode)
$this->assertEquals('expected', $actual);
```

## 5. Security & Safety

- **Never use real extension paths** in tests
- **Use temporary directories** for filesystem tests
- **Clean up** any created files in `tearDown()`
- **Mock external dependencies** (PackageManager, etc.)

## 6. PR/Commit Checklist

Before committing changes to Tests/:

- [ ] All tests pass: `ddev exec .Build/bin/phpunit`
- [ ] New classes have corresponding test files
- [ ] Test files use `#[CoversClass()]` attribute
- [ ] Test methods use `#[Test]` attribute
- [ ] Assertions use `self::assert*()` syntax
- [ ] No `@group skip` or disabled tests without issue reference

## 7. Good vs Bad Examples

### Unit Test Structure

```php
// Good: Focused, single-assertion tests
#[Test]
public function isStrongReturnsTrueForStrongIndicator(): void
{
    $match = new ScanMatch('test.php', '/test.php', 1, 'strong', 'msg', 'Matcher');

    self::assertTrue($match->isStrong());
    self::assertFalse($match->isWeak());
}

// Bad: Multiple unrelated assertions
#[Test]
public function testEverything(): void
{
    $match = new ScanMatch(...);
    self::assertTrue($match->isStrong());
    self::assertSame('test.php', $match->file);
    self::assertSame([], $match->restFiles);
    // Testing too many things at once
}
```

### Test Data

```php
// Good: Meaningful test data
$match = new ScanMatch(
    file: 'Classes/Test.php',
    absolutePath: '/var/www/ext/Classes/Test.php',
    line: 42,
    indicator: 'strong',
    message: 'Deprecated method call',
    matcherClass: 'TYPO3\\CMS\\Install\\Php\\Matcher\\MethodCallMatcher',
    restFiles: ['Deprecation-12345.rst'],
);

// Bad: Minimal/unclear test data
$match = new ScanMatch('a', 'b', 0, 'x', 'y', 'z');
```

### Edge Case Testing

```php
// Good: Test edge cases explicitly
#[Test]
public function fromMatcherOutputHandlesMissingFields(): void
{
    $rawMatch = []; // Empty input

    $match = ScanMatch::fromMatcherOutput($rawMatch, 'test.php', '/test.php', 'Matcher');

    self::assertSame(0, $match->line);
    self::assertSame('strong', $match->indicator);
    self::assertSame('Unknown issue', $match->message);
}

#[Test]
public function fromMatcherOutputFiltersInvalidRestFiles(): void
{
    $rawMatch = [
        'restFiles' => ['Valid.rst', 123, null, 'Another.rst', ['nested']],
    ];

    $match = ScanMatch::fromMatcherOutput($rawMatch, 'test.php', '/test.php', 'Matcher');

    self::assertSame(['Valid.rst', 'Another.rst'], $match->restFiles);
}
```

## 8. When Stuck

- **TYPO3 Testing Framework:** https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/Testing/
- **PHPUnit 10 Docs:** https://docs.phpunit.de/en/10.5/
- **Functional Test Setup:** See `Tests/Functional/.gitkeep` for bootstrap hints
- **Mock Objects:** Use PHPUnit's `createMock()` or `createStub()`

## 9. House Rules (Scope-Specific)

- **Coverage Target:** Aim for 80%+ line coverage on Classes/
- **No Slow Tests:** Unit tests should run < 100ms each
- **Functional Tests:** Reserve for command integration testing only
- **Data Providers:** Use `#[DataProvider('providerName')]` for parameterized tests
