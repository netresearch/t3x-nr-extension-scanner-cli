<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2026-08-19 -->

# AGENTS.md (root)

**Precedence:** The **closest AGENTS.md** to changed files wins. Root holds global defaults only.

## Project Overview

TYPO3 Extension Scanner CLI (`nr_extension_scanner_cli`) - CLI command to scan TYPO3 extensions for deprecated/removed API usage. Enables CI/CD integration for upgrade compatibility checking.

**Author:** Netresearch DTT GmbH | **License:** MIT | **TYPO3:** 12.4, 13.4, 14.3 | **PHP:** 8.2+ (see `composer.json` / `ext_emconf.php` for the authoritative constraints)

## Global Rules

- Keep PRs small (~300 net LOC)
- Conventional Commits: `type(scope): subject`
- Ask before: heavy deps, architectural changes, new matchers
- Never commit secrets, credentials, or PII
- Follow PSR-12 coding standards
- Maintain PHPStan level 10 compliance

## Commands

Make targets wrap the composer `ci:*` scripts (see `Makefile` and `composer.json`). Inside DDEV, prefix with `ddev exec`.

| Check | Command |
|-------|---------|
| Code style (check) | `make cgl` (= `composer ci:test:php:cgl`) |
| Code style (fix) | `make cgl-fix` (= `composer ci:cgl`) |
| Static analysis | `make phpstan` (= `composer ci:test:php:phpstan`) |
| Unit tests | `make test-unit` (= `composer ci:test:php:unit`) |
| Functional tests | `make test-functional` (= `composer ci:test:php:functional`) |
| All tests | `make test` |
| Rector (dry-run) | `composer ci:test:php:rector` |

## Architecture Quick Reference

Full component map: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)

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

## Commit Signing

Signed commits are required: `git commit -S --signoff`. The `require-signed-commits` ruleset on the default branch rejects unsigned commits at merge time, and the DCO check additionally requires the `Signed-off-by` trailer. Quickest setup is SSH signing — register your SSH key as a *signing key* on your GitHub account, then `git config --global gpg.format ssh && git config --global user.signingkey ~/.ssh/<key>.pub`.
