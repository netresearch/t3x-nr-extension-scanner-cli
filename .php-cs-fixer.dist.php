<?php

/*
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

$createConfig = require __DIR__ . '/.Build/vendor/netresearch/typo3-ci-workflows/config/php-cs-fixer/config.php';

return $createConfig(
    "SPDX-License-Identifier: MIT\nSPDX-FileCopyrightText: Netresearch DTT GmbH",
    __DIR__,
);
