<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2025-12-09 -->

# AGENTS.md (root)

**Precedence:** The **closest AGENTS.md** to changed files wins. Root holds global defaults only.

## Project Overview

TYPO3 Extension Scanner CLI (`nr_extension_scanner_cli`) - CLI command to scan TYPO3 extensions for deprecated/removed API usage. Enables CI/CD integration for upgrade compatibility checking.

**Author:** Netresearch DTT GmbH | **License:** MIT | **TYPO3:** 12.4, 13.4, 14.0 | **PHP:** 8.2+

## Global Rules

- Keep PRs small (~300 net LOC)
- Conventional Commits: `type(scope): subject`
- Ask before: heavy deps, architectural changes, new matchers
- Never commit secrets, credentials, or PII
- Follow PSR-12 coding standards
- Maintain PHPStan level 10 compliance

## Pre-commit Checks

| Check | Command |
|-------|---------|
| Lint | `ddev exec .Build/bin/php-cs-fixer fix --dry-run --diff` |
| Static Analysis | `ddev exec .Build/bin/phpstan analyse` |
| Tests | `ddev exec .Build/bin/phpunit -c phpunit.xml` |
| All Checks | `ddev exec composer test` (when configured) |

## Architecture Quick Reference

```
Classes/
├── Command/ExtensionScannerCommand.php  # CLI entry point (extension:scan)
├── Dto/ScanMatch.php                    # Immutable scan result DTO
├── Output/                              # Output formatters (table/json/checkstyle)
│   ├── OutputFormatterInterface.php
│   ├── TableOutputFormatter.php
│   ├── JsonOutputFormatter.php
│   └── CheckstyleOutputFormatter.php
└── Service/ExtensionScannerService.php  # Core scanning logic using TYPO3 matchers
```

## Key Patterns

- **DTOs:** Use `final readonly class` with named constructor parameters
- **Services:** Constructor injection via `Configuration/Services.yaml`
- **Output:** Strategy pattern via `OutputFormatterInterface`
- **Testing:** PHPUnit 10+ attributes (`#[Test]`, `#[CoversClass]`)

## Index of Scoped AGENTS.md

- `./Classes/AGENTS.md` — PHP source code patterns
- `./Tests/AGENTS.md` — Testing conventions

## When Instructions Conflict

Nearest AGENTS.md wins. User prompts override files.
