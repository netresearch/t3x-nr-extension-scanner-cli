.. _introduction:

============
Introduction
============

The Extension Scanner CLI provides the same functionality as the TYPO3 Install
Tool's Extension Scanner, but accessible from the command line for CI/CD
integration.

Features
========

- **Scan extensions** for deprecated or removed TYPO3 Core API usage.
- **Multiple output formats**: human-readable table, JSON, and Checkstyle XML.
- **CI/CD ready**: non-zero exit codes on findings, machine-readable output.
- **Flexible targeting**: scan specific extensions, custom paths, or all extensions.
- **Strong/weak indicators**: distinguish between definite and potential matches.

How it works
============

The extension reuses the existing Extension Scanner infrastructure from
``EXT:install``. It uses the same matcher classes and deprecation configurations,
ensuring results are identical to the Install Tool's Extension Scanner.

The scanner analyzes PHP files using static code analysis to detect:

- Method calls to deprecated or removed methods.
- Usage of deprecated or removed classes.
- Access to deprecated or removed constants or properties.
- Deprecated function calls.
- Deprecated array accesses.

Understanding results
=====================

Strong matches
--------------

Strong matches indicate **definite usage** of deprecated or removed TYPO3 API.
These must be fixed before upgrading TYPO3.

Weak matches
------------

Weak matches indicate **potential usage** that requires manual verification.
Review these manually to determine if action is needed.

Technical notes
===============

.. note::

   This extension depends on internal TYPO3 Core classes marked ``@internal``.
   While these classes are stable in practice, they may change between TYPO3
   versions. This is acceptable for a development/CI tool since:

   - It is not production runtime code.
   - Breaking changes would be apparent immediately.
   - The benefit outweighs the API stability concern.
