# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x.x   | :white_check_mark: |

## Reporting a Vulnerability

We take security vulnerabilities seriously. If you discover a security issue, please report it responsibly.

### How to Report

1. **Do NOT** open a public GitHub issue for security vulnerabilities
2. Send an email to **security@netresearch.de** with:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Any suggested fixes (optional)

### What to Expect

- **Initial Response**: Within 48 business hours
- **Status Update**: Within 5 business days
- **Resolution Timeline**: Depends on severity
  - Critical: 1-7 days
  - High: 7-14 days
  - Medium: 14-30 days
  - Low: Next release cycle

### Disclosure Policy

- We follow coordinated disclosure practices
- Security fixes will be released as soon as possible
- Credit will be given to reporters (unless anonymity is requested)
- Public disclosure after fix is released and users have time to update

## Security Best Practices

When using this extension:

1. **Keep Updated**: Always use the latest version
2. **CI/CD Integration**: Run scans in isolated CI environments
3. **Access Control**: Limit CLI access to authorized developers
4. **Output Handling**: Be cautious with scan output in logs (may contain file paths)

## Known Security Considerations

This extension:
- Reads PHP files for static analysis (no code execution)
- Uses TYPO3 core's Extension Scanner matchers
- Does not make network requests
- Does not store sensitive data

## Contact

- Security issues: security@netresearch.de
- General support: info@netresearch.de
- Website: https://www.netresearch.de
