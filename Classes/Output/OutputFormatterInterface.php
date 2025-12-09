<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Netresearch\ExtensionScannerCli\Output;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface for Extension Scanner output formatters.
 *
 * Implement this interface to create custom output formats for scan results.
 */
interface OutputFormatterInterface
{
    /**
     * Format and output scan results.
     *
     * @param OutputInterface $output The console output to write to
     * @param array<string, array<int, array<string, mixed>>> $allMatches Matches grouped by extension key
     * @param int $totalStrong Total number of strong (definite) matches
     * @param int $totalWeak Total number of weak (potential) matches
     */
    public function format(
        OutputInterface $output,
        array $allMatches,
        int $totalStrong,
        int $totalWeak,
    ): void;
}
