<!-- SPDX-License-Identifier: MIT -->
<!-- SPDX-FileCopyrightText: Netresearch DTT GmbH -->
# Security assurance

What users of `nr_extension_scanner_cli` can and cannot expect in terms of security, the threat model behind it, and the argument that the code meets it. Every statement refers to the code in this repository; where the code and this file disagree, the code wins — update this file in the same pull request. Components and data flow are described in [ARCHITECTURE.md](ARCHITECTURE.md). Vulnerabilities are reported as described in [SECURITY.md](../SECURITY.md).

## What the extension does, security-wise

- It adds one console command, `extension:scan` (`Classes/Command/ExtensionScannerCommand.php`), registered in `Configuration/Services.yaml` with `schedulable: false`, so it cannot be run from the TYPO3 scheduler. The extension adds no backend module, route, frontend plugin, middleware or database table.
- It reads the `*.php` files below the paths it is given, parses them with `nikic/php-parser` and runs the TYPO3 core Extension Scanner matchers of `typo3/cms-install` over the syntax tree (`Classes/Service/ExtensionScannerService.php`).
- It writes the findings to standard output as a table, JSON or Checkstyle XML (`Classes/Output/`). The command's messages, warnings, section headers and progress counter go through Symfony's `SymfonyStyle` to standard error, so a redirected report contains only the findings; the summary of the table format is part of that report.

## Security expectations

Users can expect:

- **Scanned code is parsed, never executed.** `ExtensionScannerService::scanFile()` reads a file with `getContents()` and hands the string to `Parser::parse()`. No scanned file is passed to `include`, `require`, `eval` or a process. The only `require` in `Classes/` loads the matcher configuration of `EXT:install` (`getMatcherConfigurations()`), whose file names are the constants in `MATCHER_CONFIGURATIONS`. `Tests/Unit/Service/ExtensionScannerServiceTest.php` scans a file whose only statement would write a marker file and asserts that the marker does not exist.
- **A broken file does not stop the scan.** A syntax error raises `PhpParser\Error`, which `scanFile()` catches; the file yields no findings and, with `--verbose-parse-errors`, a warning.
- **The extension writes no files and makes no network requests.** `Classes/` contains no file-writing, process or network function; the command's only output is the console output. Redirecting it to a file (`> report.xml`) is done by the caller's shell.
- **Machine-readable output is encoded.** The JSON formatter uses `json_encode()` with `JSON_THROW_ON_ERROR`, so a value it cannot encode ends the run with an exception instead of truncated JSON. The Checkstyle formatter builds the document with `XMLWriter`, which escapes attribute values, and replaces control characters, which XML 1.0 cannot represent, with `?`; `formatEscapesSpecialXmlCharacters` and `controlCharactersInFileNamesAndMessagesAreReplaced` in `Tests/Unit/Output/CheckstyleOutputFormatterTest.php` pin that. Both documents are written without the console formatter (`OutputInterface::OUTPUT_RAW`), so console tags such as `<info>` in a file name stay unchanged (`fileNamesWithConsoleTagsAreWrittenUnchanged`).
- **Text from scanned code is shown literally on the console.** File names, extension keys and parser messages pass through `ConsoleText` (`Classes/Output/ConsoleText.php`) before table or console output: control characters become `?`, and console tags are escaped. `Tests/Unit/Output/TableOutputFormatterTest.php` and `Tests/Unit/Command/ExtensionScannerCommandTest.php` pin that.

Users cannot expect:

- **A security verdict.** The scanner reports usage of deprecated or removed TYPO3 API. It does not look for vulnerabilities.
- **A result that the scanned code cannot influence.** The TYPO3 core matchers honour the comments `@extensionScannerIgnoreFile` and `@extensionScannerIgnoreLine` in the scanned code (`CodeStatistics` and `AbstractCoreMatcher` in `typo3/cms-install`). A file that cannot be parsed contributes no findings and does not change the exit code. A clean result is therefore no proof that code is free of deprecated API.
- **Confinement to the scanned directory.** The files are read with the permissions of the user who runs `bin/typo3`. Symfony Finder follows a symbolic link to a file (not to a directory), so a link inside the scanned tree is read like any other `*.php` file. Directories named `vendor`, `node_modules` and `.Build`, VCS directories and dot-files are skipped. File contents are not printed; with `--verbose-parse-errors` the parser's error message, which can quote a token of the file, is.
- **Output free of local paths.** JSON (`absolutePath`) and Checkstyle (`file name`) contain the absolute path of every file with a finding.
- **Limits on input size.** There is no limit on the number or size of scanned files; an input that exhausts PHP's memory limit ends the run with a PHP error instead of a report.
- **Stable internal API.** The matchers are `@internal` classes of TYPO3 core (see `Build/phpstan/phpstan.neon` and README "Technical Notes").

The command bootstraps TYPO3 like every `bin/typo3` command and calls `Bootstrap::initializeBackendAuthentication()`, which logs in the TYPO3 backend user `_cli_`; TYPO3 core creates that user on first use (`CommandLineUserAuthentication` in `typo3/cms-core`). The extension itself issues no database query.

## Threat model and trust boundaries

Actors: the operator who runs the command (a developer or a CI job, trusted), the code being scanned (third-party or unreviewed extensions, untrusted), the TYPO3 installation with `typo3/cms-install` (trusted), and the tools that read the report (CI systems, IDEs).

| Boundary | Untrusted or semi-trusted input | Control |
|----------|---------------------------------|---------|
| Scanned files → scanner | Content and names of the `*.php` files below the scanned path | Parsed into a syntax tree, never included (`scanFile()`); parse errors caught per file; pinned by `scanFileParsesTheScannedCodeWithoutExecutingIt` and `scanFileReportsAParseErrorAndReturnsNoMatches` |
| Command line → command | Extension keys, `--path`, `--format` | `--format` must be one of `SUPPORTED_FORMATS`; extension keys must be active packages (`PackageManager::isPackageActive()`); `--path` must be an existing directory (`is_dir()`) and is only used as the root for reading. The operator is trusted: the command can read whatever the operator can |
| TYPO3 core → scanner | Matcher configuration files in `EXT:install/Configuration/ExtensionScanner/Php/` | Part of the installed TYPO3 core and trusted; loaded with `require` from a path built from constants only |
| Scanner → report consumer | Findings, including file names from the scanned tree | JSON via `json_encode()`, Checkstyle via `XMLWriter`, both written raw; control characters replaced in Checkstyle |
| Scanner → terminal | File names and parser messages in table output and warnings | `ConsoleText`: control characters replaced, console tags escaped |
| Package registry → installation | `nikic/php-parser`, TYPO3 core packages, dev tools | Composer Audit, Dependency Review and the licence check on pull requests (`.github/workflows/checks.yml`); Renovate updates (`renovate.json`) |

## Secure design principles applied

- **Small attack surface:** a single CLI command, excluded from the scheduler; no web entry point, no network access, no file writes.
- **Input is data:** scanned code only ever reaches the parser; nothing derived from it selects a file to load or a class to instantiate — the matcher classes come from the constant `MATCHER_CONFIGURATIONS`.
- **Allowlists:** the output format is checked against `SUPPORTED_FORMATS`, extension keys against the active packages.
- **Economy of mechanism:** the detection logic is TYPO3 core's own Extension Scanner, not a reimplementation.
- **Immutable results:** each finding is a `final readonly` value object (`Classes/Dto/ScanMatch.php`).
- **Strict typing:** every class declares `strict_types=1`; PHPStan runs at level 10 over `Classes/` (`Build/phpstan/phpstan.neon`).

## Countering common weaknesses

| Weakness (CWE / OWASP) | Counter |
|------------------------|---------|
| Code injection, file inclusion (CWE-94, CWE-98) | Scanned files are parsed, never included or evaluated; the only `require` loads core configuration from a constant path; unit test above |
| OS command injection (CWE-78) | `Classes/` starts no process |
| Path traversal (CWE-22) | Paths come from the operator or from `PackageManager`; the extension only reads below them and writes nothing |
| XML external entities (CWE-611) | The extension parses no XML; Checkstyle XML is only written, with `XMLWriter` |
| Injection into reports and terminal output (CWE-116, CWE-150) | `json_encode()` and `XMLWriter` encode all values; `ConsoleText` replaces control characters and escapes console tags in console output |
| Server-side request forgery (CWE-918) | The extension makes no outgoing requests |
| SQL injection (CWE-89, A03:2021) | `Classes/` issues no database queries |
| Hard-coded credentials (CWE-798) | None in the code; Betterleaks scans every pull request (`checks.yml`) |
| Vulnerable and outdated components (A06:2021) | Composer Audit and Dependency Review on pull requests (`checks.yml`), Renovate updates (`renovate.json`) |

The checks that run on every pull request are listed in [CONTRIBUTING.md](../CONTRIBUTING.md#governance-and-policies).
