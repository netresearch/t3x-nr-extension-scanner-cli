.. _installation:

============
Installation
============

Requirements
============

- TYPO3 12.4 LTS, 13.4 LTS, or 14.x.
- PHP 8.2 or higher.

Installation via Composer
=========================

The recommended way to install this extension is via Composer:

.. code-block:: bash

   composer require --dev netresearch/extension-scanner-cli

.. note::

   The extension is typically installed as a development dependency since it is
   only needed during development and CI/CD pipelines, not in production.

Installation via TER
====================

Alternatively, you can download and install ``nr_extension_scanner_cli`` from the
TYPO3 Extension Repository (TER).

1. Go to the Extension Manager in the TYPO3 backend.
2. Search for "Extension Scanner CLI".
3. Install the extension.
