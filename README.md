# TYPO3 Extension Scanner CLI

[![TYPO3 12](https://img.shields.io/badge/TYPO3-12-orange.svg)](https://get.typo3.org/version/12)
[![TYPO3 13](https://img.shields.io/badge/TYPO3-13-orange.svg)](https://get.typo3.org/version/13)
[![TYPO3 14](https://img.shields.io/badge/TYPO3-14-orange.svg)](https://get.typo3.org/version/14)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

CLI command to scan TYPO3 extensions for deprecated/removed API usage. Provides the Install Tool Extension Scanner functionality on the command line for CI/CD integration.

## Features

- **Scan extensions** for deprecated or removed TYPO3 Core API usage
- **Multiple output formats**: Human-readable table, JSON, and Checkstyle XML
- **CI/CD ready**: Non-zero exit codes on findings, machine-readable output
- **Flexible targeting**: Scan specific extensions, custom paths, or all extensions
- **Strong/weak indicators**: Distinguish between definite and potential matches

## Installation

### Composer (recommended)

```bash
composer require --dev netresearch/extension-scanner-cli
```

### TYPO3 Extension Repository (TER)

Download and install `nr_extension_scanner_cli` from the TYPO3 Extension Repository.

## Usage

### Basic Usage

```bash
# Scan a specific extension
bin/typo3 extension:scan my_extension

# Scan multiple extensions
bin/typo3 extension:scan ext1 ext2 ext3

# Scan a custom path (e.g., extension in development)
bin/typo3 extension:scan --path=/path/to/extension

# Scan all third-party extensions
bin/typo3 extension:scan --all
```

### Output Formats

```bash
# Human-readable table (default)
bin/typo3 extension:scan my_extension

# JSON output for processing
bin/typo3 extension:scan my_extension --format=json

# Checkstyle XML for CI tools
bin/typo3 extension:scan my_extension --format=checkstyle > report.xml
```

### CI/CD Integration

```bash
# Fail on strong matches only (default)
bin/typo3 extension:scan my_extension

# Fail on both strong and weak matches
bin/typo3 extension:scan my_extension --fail-on-weak

# Suppress progress output for cleaner logs
bin/typo3 extension:scan my_extension --no-progress

# Include system extensions in --all scan
bin/typo3 extension:scan --all --include-system
```

### Exit Codes

| Code | Meaning |
|------|---------|
| 0 | No issues found (or only weak matches without `--fail-on-weak`) |
| 1 | Strong matches found (definite compatibility issues) |
| 2 | Only weak matches found (with `--fail-on-weak` enabled) |

## Example Output

### Table Format

```
Results for: my_extension
================================================================================

+------------------+------+---------------+----------------------------------+-----------+
| File             | Line | Type          | Message                          | Indicator |
+------------------+------+---------------+----------------------------------+-----------+
| Classes/Foo.php  | 45   | Method Call   | Method getData() is deprecated   |  STRONG   |
| Classes/Bar.php  | 123  | Class Name    | Class OldClass has been removed  |  STRONG   |
| Classes/Baz.php  | 78   | Property      | Property $foo may be deprecated  |   WEAK    |
+------------------+------+---------------+----------------------------------+-----------+

Summary
-------

[ERROR] Found 2 strong match(es) that WILL break on upgrade.
[WARNING] Found 1 weak match(es) that MAY need attention.

Total issues: 3 (2 strong, 1 weak)
```

### JSON Format

```json
{
  "summary": {
    "total": 3,
    "strong": 2,
    "weak": 1,
    "extensions_scanned": 1,
    "timestamp": "2025-12-09T15:30:00+00:00"
  },
  "extensions": [
    {
      "key": "my_extension",
      "total": 3,
      "strong": 2,
      "weak": 1,
      "matches": [
        {
          "file": "Classes/Foo.php",
          "line": 45,
          "indicator": "strong",
          "message": "Method getData() is deprecated"
        }
      ]
    }
  ]
}
```

## GitLab CI Example

```yaml
extension-scan:
  stage: test
  script:
    - composer install
    - bin/typo3 extension:scan my_extension --format=checkstyle > gl-code-quality-report.xml
  artifacts:
    reports:
      codequality: gl-code-quality-report.xml
  allow_failure: true
```

## GitHub Actions Example

```yaml
- name: Scan for deprecated API
  run: |
    bin/typo3 extension:scan my_extension --format=checkstyle > extension-scan.xml

- name: Upload scan results
  uses: actions/upload-artifact@v4
  with:
    name: extension-scan-results
    path: extension-scan.xml
```

## Understanding Results

### Strong Matches

Strong matches indicate **definite usage** of deprecated or removed TYPO3 API:
- Direct method calls to removed methods
- Usage of removed classes
- Access to removed constants or properties

**These must be fixed before upgrading TYPO3.**

### Weak Matches

Weak matches indicate **potential usage** that requires manual verification:
- Method names that match deprecated methods but might be custom implementations
- Variable names that match deprecated patterns
- String literals that might reference deprecated functionality

**Review these manually to determine if action is needed.**

## Technical Notes

This extension reuses the existing Extension Scanner infrastructure from `EXT:install`. It uses the same matcher classes and deprecation configurations, ensuring results are identical to the Install Tool's Extension Scanner.

**Note**: This extension depends on internal TYPO3 Core classes marked `@internal`. While these classes are stable in practice, they may change between TYPO3 versions. This is acceptable for a development/CI tool since:
1. It's not production runtime code
2. Breaking changes would be apparent immediately
3. The benefit outweighs the API stability concern

## Requirements

- TYPO3 12.4 LTS, 13.4 LTS, or 14.x
- PHP 8.2 or higher

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

Documentation is licensed under [CC BY 4.0](https://creativecommons.org/licenses/by/4.0/).

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Credits

Based on the core patch by Sebastian Mendel: [TYPO3 Review #92021](https://review.typo3.org/c/Packages/TYPO3.CMS/+/92021)

Developed by [Netresearch DTT GmbH](https://www.netresearch.de)
