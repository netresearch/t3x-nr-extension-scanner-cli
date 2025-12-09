.. _usage:

=====
Usage
=====

Basic usage
===========

Scan a specific extension
-------------------------

.. code-block:: bash

   bin/typo3 extension:scan my_extension

Scan multiple extensions
------------------------

.. code-block:: bash

   bin/typo3 extension:scan ext1 ext2 ext3

Scan a custom path
------------------

.. code-block:: bash

   bin/typo3 extension:scan --path=/path/to/extension

Scan all third-party extensions
-------------------------------

.. code-block:: bash

   bin/typo3 extension:scan --all

Output formats
==============

Table format (default)
----------------------

Human-readable output for terminal use:

.. code-block:: bash

   bin/typo3 extension:scan my_extension

JSON format
-----------

Machine-readable output for processing:

.. code-block:: bash

   bin/typo3 extension:scan my_extension --format=json

Checkstyle XML format
---------------------

For CI tools (Jenkins, GitLab CI, GitHub Actions):

.. code-block:: bash

   bin/typo3 extension:scan my_extension --format=checkstyle > report.xml

CI/CD integration
=================

GitHub Actions
--------------

.. code-block:: yaml

   - name: Scan for deprecated API
     run: |
       bin/typo3 extension:scan my_extension --format=checkstyle > extension-scan.xml

   - name: Upload scan results
     uses: actions/upload-artifact@v4
     with:
       name: extension-scan-results
       path: extension-scan.xml

GitLab CI
---------

.. code-block:: yaml

   extension-scan:
     stage: test
     script:
       - composer install
       - bin/typo3 extension:scan my_extension --format=checkstyle > gl-code-quality-report.xml
     artifacts:
       reports:
         codequality: gl-code-quality-report.xml
     allow_failure: true

Jenkins
-------

Use the Checkstyle format with the Warnings Next Generation plugin:

.. code-block:: groovy

   stage('Extension Scan') {
       steps {
           sh 'bin/typo3 extension:scan my_extension --format=checkstyle > extension-scan.xml'
       }
       post {
           always {
               recordIssues tools: [checkStyle(pattern: 'extension-scan.xml')]
           }
       }
   }

Advanced usage
==============

Fail on weak matches
--------------------

By default, only strong matches cause a non-zero exit code. To also fail on
weak matches:

.. code-block:: bash

   bin/typo3 extension:scan my_extension --fail-on-weak

Suppress progress output
------------------------

For cleaner CI/CD logs:

.. code-block:: bash

   bin/typo3 extension:scan my_extension --no-progress --format=json

Include system extensions
-------------------------

When using ``--all``, system extensions are excluded by default. To include them:

.. code-block:: bash

   bin/typo3 extension:scan --all --include-system
