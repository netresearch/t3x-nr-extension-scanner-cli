# Architecture

Agent-facing component map. For coding conventions see [`../Classes/AGENTS.md`](../Classes/AGENTS.md); for user documentation see [`../Documentation/`](../Documentation/).

## System Overview

`nr_extension_scanner_cli` is a TYPO3 extension that exposes the TYPO3 core Extension Scanner (from `typo3/cms-install`) as a CLI command, `bin/typo3 extension:scan`. It parses extension PHP files with `nikic/php-parser`, runs them through the core scanner matchers to find deprecated/removed API usage, and renders the findings as a table, JSON, or Checkstyle XML — the latter two for CI/CD pipelines. It is a developer/CI tool: it ships no frontend plugins, no TCA, and no database tables.

## Components

| Component | Path | Responsibility |
|-----------|------|----------------|
| CLI command | `Classes/Command/ExtensionScannerCommand.php` | Entry point `extension:scan`; resolves targets (extension keys, `--path`, `--all`), selects the formatter (`--format=table\|json\|checkstyle`), maps results to the exit code (`--fail-on-weak`) |
| Scanner service | `Classes/Service/ExtensionScannerService.php` | Core logic: parses PHP files (`nikic/php-parser`), traverses them with the TYPO3 core matchers from `TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\*`, collects matches |
| Result DTO | `Classes/Dto/ScanMatch.php` | Immutable `final readonly` value object per finding (file, line, indicator strong/weak, message, matcher, RST references) |
| Formatter contract | `Classes/Output/OutputFormatterInterface.php` | Strategy interface for output rendering |
| Formatters | `Classes/Output/TableOutputFormatter.php`, `JsonOutputFormatter.php`, `CheckstyleOutputFormatter.php` | Render matches for humans (table) or CI consumers (JSON, Checkstyle XML) |
| DI wiring | `Configuration/Services.yaml` | Autowiring; registers the command under `extension:scan` (`schedulable: false`) |

## Data Flow

1. `ExtensionScannerCommand` bootstraps backend authentication and resolves the scan targets via the TYPO3 `PackageManager` (extension keys, a custom `--path`, or `--all` third-party extensions).
2. For each target, `ExtensionScannerService` walks the PHP files, parses each with `nikic/php-parser`, and applies the core matcher set (method calls, class names, constants, property access, annotations, …).
3. Raw matcher output is normalized into `ScanMatch` DTOs via `ScanMatch::fromMatcherOutput()`.
4. The chosen `OutputFormatterInterface` implementation renders all matches; the command exits non-zero on strong matches (and on weak matches with `--fail-on-weak`).

## Key Decisions

- **Deliberate use of `@internal` TYPO3 API:** the core Extension Scanner matcher classes are `@internal`. This is accepted for a dev tool and the PHPStan ignores documenting it live in `Build/phpstan/phpstan.neon`.
- **Strategy pattern for output:** new formats are added by implementing `OutputFormatterInterface`; see `Classes/AGENTS.md` for the pattern.
- **CI matrix:** PHP 8.2–8.5 × TYPO3 `^12.4`/`^13.4`/`^14.3` via the shared reusable `netresearch/typo3-ci-workflows` (`.github/workflows/ci.yml`).

There is no ADR directory; decisions are recorded here and in the config files referenced above.
