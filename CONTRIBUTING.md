<!-- SPDX-License-Identifier: MIT -->
<!-- SPDX-FileCopyrightText: Netresearch DTT GmbH -->
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
git clone https://github.com/netresearch/t3x-nr-extension-scanner-cli.git
cd t3x-nr-extension-scanner-cli

# Start DDEV
ddev start

# Install dependencies
ddev composer install
```

### Running Tests

```bash
# Unit tests
ddev exec .Build/bin/phpunit -c Build/phpunit.xml

# Or without DDEV
.Build/bin/phpunit -c Build/phpunit.xml
```

### Code Quality Tools

```bash
# PHP CS Fixer
ddev exec .Build/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff

# PHPStan
ddev exec .Build/bin/phpstan analyse -c Build/phpstan/phpstan.neon

# Fix coding standards
ddev exec .Build/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php
```

## Coding Standards

### PHP

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standard
- Use [TYPO3 Coding Guidelines](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/CodingGuidelines/Index.html)
- Add type declarations to all methods
- Use strict types (`declare(strict_types=1);`)

### Commit Messages

Commit messages follow [Conventional Commits](https://www.conventionalcommits.org/): `type(scope): subject`.

- Use present tense ("Add feature" not "Added feature")
- Use imperative mood ("Fix bug" not "Fixes bug")
- Reference issues when applicable (`Fixes #123`)
- Keep the first line under 72 characters

Example:
```
feat(scanner): support custom matcher configurations

This adds the ability to configure additional matchers beyond
the default TYPO3 core matchers.

Resolves: #42
```

### Commit Types

- `feat` - New functionality
- `fix` - Bug fixes
- `chore`, `ci`, `build` - Maintenance, CI and build changes
- `refactor` - Code changes that neither fix a bug nor add a feature
- `docs` - Documentation changes
- `test` - Test-related changes

## Project Structure

```
extension_scanner_cli/
├── Classes/
│   ├── Command/           # Symfony console commands
│   ├── Dto/               # Immutable scan result objects
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
#[Test]
public function formatOutputsValidJsonStructure(): void
{
    // Arrange
    $formatter = new JsonOutputFormatter();
    $output = new BufferedOutput();
    $matches = [...];

    // Act
    $formatter->format($output, $matches, 1, 2);

    // Assert
    self::assertJson($output->fetch());
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

## Governance and policies

This extension follows the organisation-wide Netresearch policies:

- [Governance](https://github.com/netresearch/.github/blob/main/GOVERNANCE.md): ownership, roles and their responsibilities, how decisions are made and how disagreements are resolved.
- [Roadmap](https://github.com/netresearch/.github/blob/main/ROADMAP.md): planned and excluded work for the next twelve months. It applies here because this repository has no `ROADMAP.md` of its own.
- [Handling of dependency and code analysis findings](https://github.com/netresearch/.github/blob/main/SECURITY.md#handling-of-dependency-and-code-analysis-findings): which vulnerability, licence and static-analysis findings must be fixed, by when, and how exceptions are recorded.
- [Secret management](https://github.com/netresearch/.github/blob/main/SECURITY.md#secret-management): where CI and release credentials are stored, who may use them, and when they are rotated.
- [Access roster](https://github.com/netresearch/.github/blob/main/docs/access-roster.md): the accounts with admin or write access to this repository.

Checks that run on every pull request in this repository:

- `.github/workflows/checks.yml`: Composer Audit (fails on any advisory for an installed package that `config.audit.ignore` in `composer.json` does not exempt) and Opengrep SAST (`--config auto --error --severity WARNING`: fails on findings of rules with severity WARNING; that flag leaves out the rules with severity ERROR), both through `security.yml` of `netresearch/typo3-ci-workflows`; Dependency Review (fails on added or changed dependencies with a vulnerability of severity high or higher); the PHP licence check (`license-check.yml`, fails when a Composer dependency declares exactly `SSPL` or `BSL`; identifiers such as `SSPL-1.0` or `BUSL-1.1` do not match); Betterleaks secret scanning; zizmor and CodeQL for the workflow files (the repository has no JavaScript, and CodeQL has no PHP analyser). The `fuzz` job is called but runs nothing here, as `Build/phpunit.xml` defines no fuzz test suite. The `pr-quality` job (`pr-quality.yml` of `netresearch/.github`) reports the size of the pull request and, depending on the author's repository role, approves it. The `All security checks` job fails when any of these jobs failed or was cancelled.
- `.github/workflows/ci.yml`: PHP lint, code style (PHP-CS-Fixer), PHPStan at level 10, Rector in dry-run mode, the unit tests for PHP 8.2 to 8.5 and TYPO3 12.4, 13.4 and 14.3, and a render of `Documentation/`. Functional tests are switched off; `Tests/Functional/` holds no tests yet. The `All CI checks` job fails when any of these jobs failed or was cancelled.
- `.github/workflows/check-template-drift.yml`: fails when a file under `.github/` that the typo3-extension template of `netresearch/.github` manages differs from the template, except the files `.github/template.yaml` lists as intentional drift.
- `.github/workflows/harness-verify.yml`: `Build/Scripts/verify-harness.sh` checks that the `AGENTS.md` files and `docs/` match the repository.

Exceptions: `config.audit.ignore` in `composer.json` exempts eight Packagist advisories from Composer Audit; the file records no reason for them.

## Questions?

- Open an issue for questions
- Contact: [GitHub Issues](https://github.com/netresearch/t3x-nr-extension-scanner-cli/issues)
- Website: https://www.netresearch.de

## License

By contributing, you agree that your contributions will be licensed under the MIT license.

## Commit Signing

All commits must be cryptographically signed and carry a DCO sign-off: `git commit -S --signoff`. The `require-signed-commits` ruleset on the default branch enforces the signature (the "Verified" badge on GitHub); the DCO check enforces the `Signed-off-by` trailer — these are two different things and both are required. Quickest setup is SSH signing: register your SSH key as a *signing key* on your GitHub account, then `git config --global gpg.format ssh && git config --global user.signingkey ~/.ssh/<key>.pub`.
