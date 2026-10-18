<?php

/*
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

// Included by Build/phpstan/phpstan.neon. CI removes
// netresearch/typo3-ci-workflows on the TYPO3 ^12.4 cells (remove-dev-deps
// in .github/workflows/ci.yml), because its saschaegerer/phpstan-typo3
// requires typo3/cms-core ^13.4.3. phpstan/extension-installer and the
// PHPStan rule packages it registers go with it, as this repository requires
// none of them directly. A plain NEON include of the shared config would fail
// there on a missing file, so the include is made only when the package is
// installed. Without it, the ^12.4 cells keep the parameters the old config
// set and run the same analysis as before.
$shared = __DIR__ . '/../../.Build/vendor/netresearch/typo3-ci-workflows/config/phpstan/phpstan.neon';

if (is_file($shared)) {
    return ['includes' => [$shared]];
}

return [
    'parameters' => [
        'level'                     => 10,
        'treatPhpDocTypesAsCertain' => false,
        // The explicit analyseAndScan key is needed here: a plain list that
        // works in NEON arrives empty from a PHP config file.
        'excludePaths' => ['analyseAndScan' => [
            '%currentWorkingDirectory%/.Build/*',
        ]],
    ],
];
