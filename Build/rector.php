<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;

$configure = require_once __DIR__ . '/../.Build/vendor/netresearch/typo3-ci-workflows/config/rector/rector.php';

return static function (RectorConfig $rectorConfig) use ($configure): void {
    // Shared org base config: paths, code-quality sets, rule skips, importNames,
    // phpVersion and the package's ergebnis-free phpstan-rector.neon.
    $configure($rectorConfig, __DIR__ . '/..');

    // paths() REPLACES the shared list, so the standard entries are repeated
    // here to add Tests/ to the scanned tree.
    $rectorConfig->paths([
        __DIR__ . '/../Classes',
        __DIR__ . '/../Configuration',
        __DIR__ . '/../Resources',
        __DIR__ . '/../Tests',
    ]);

    // TYPO3 migration level: v13, the lowest still-supported major
    // (typo3-ci-workflows#155); raise when v13 support is dropped.
    $rectorConfig->sets([
        Typo3LevelSetList::UP_TO_TYPO3_13,
    ]);
};
