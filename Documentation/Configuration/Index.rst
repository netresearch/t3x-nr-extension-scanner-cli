.. _configuration:

=============
Configuration
=============

The Extension Scanner CLI does not require any configuration. It works
out-of-the-box with sensible defaults.

Command options
===============

All configuration is done via command-line options:

.. confval:: --format, -f
   :type: string
   :Default: table

   Output format. Available options:

   - ``table``: Human-readable table format (default).
   - ``json``: Machine-readable JSON format.
   - ``checkstyle``: Checkstyle XML format for CI tools.

.. confval:: --path, -p
   :type: string
   :Default: (none)

   Custom path to scan instead of an extension key. Useful for scanning
   extensions in development that are not yet installed.

.. confval:: --all, -a
   :type: boolean
   :Default: false

   Scan all loaded third-party extensions.

.. confval:: --no-progress
   :type: boolean
   :Default: false

   Disable progress output. Useful for cleaner CI/CD logs.

.. confval:: --fail-on-weak
   :type: boolean
   :Default: false

   Return non-zero exit code on weak matches. By default, only strong matches
   cause a non-zero exit code.

.. confval:: --include-system
   :type: boolean
   :Default: false

   Include TYPO3 system extensions when using ``--all``.

.. confval:: --verbose-parse-errors
   :type: boolean
   :Default: false

   Show parse errors for files that cannot be analyzed due to syntax errors.

Exit codes
==========

The command uses exit codes to indicate scan results:

.. list-table::
   :header-rows: 1
   :widths: 20 80

   * - Code
     - Meaning
   * - 0
     - No issues found (or only weak matches without ``--fail-on-weak``).
   * - 1
     - Strong matches found (definite compatibility issues).
   * - 2
     - Only weak matches found (with ``--fail-on-weak`` enabled).
